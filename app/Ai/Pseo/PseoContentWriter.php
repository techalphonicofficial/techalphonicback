<?php

namespace App\Ai\Pseo;

use App\Ai\AiException;
use App\Ai\AiManager;
use App\Ai\AiRequest;
use App\Ai\AiSettings;
use App\Enums\ContentStatus;
use App\Jobs\GenerateLandingContent;
use App\Models\ActivityLog;
use App\Models\AiGeneration;
use App\Models\Faq;
use App\Models\FaqCategory;
use App\Models\LandingPage;
use App\Services\SettingsService;
use App\Support\LegacyHtml;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Writes AI content straight into a programmatic page's draft fields.
 *
 * Guard rails: only non-live pages (draft / unpublished / archived) are written to, indexing stays off,
 * HTML is re-sanitised to a small whitelist, and every run is recorded in ai_generations with its token cost.
 */
class PseoContentWriter
{
    private const LIMITS = ['sections' => 6, 'benefits' => 8, 'process' => 8, 'why_choose_us' => 6, 'faqs' => 10];

    public function __construct(private AiManager $ai) {}

    /** Validates preconditions, records a queued generation and dispatches the job. */
    public function queue(LandingPage $page, ?string $batchId = null): AiGeneration
    {
        $settings = $this->ai->settings();
        $this->assertCanGenerate($page, $settings);

        $generation = AiGeneration::create([
            'subject_type' => $page->getMorphClass(),
            'subject_id' => $page->getKey(),
            'user_id' => auth()->id(),
            'batch_id' => $batchId,
            'provider' => $settings->provider(),
            'model' => $settings->model(),
            'prompt_version' => PseoPrompt::VERSION,
            'status' => 'queued',
            'fields' => $settings->fields(),
        ]);
        GenerateLandingContent::dispatch($generation->id)->afterCommit();

        return $generation;
    }

    /**
     * Queues many pages; pages that can't be written (live, already queued…) are skipped with a reason.
     *
     * @param  iterable<LandingPage>  $pages
     * @return array{queued: int, skipped: array<int, string>, batch: string}
     */
    public function queueMany(iterable $pages): array
    {
        $batch = (string) Str::uuid();
        $queued = 0;
        $skipped = [];
        foreach ($pages as $page) {
            try {
                $this->queue($page, $batch);
                $queued++;
            } catch (AiException $e) {
                $skipped[$page->getKey()] = $e->getMessage();
            }
        }

        return ['queued' => $queued, 'skipped' => $skipped, 'batch' => $batch];
    }

    public function assertCanGenerate(LandingPage $page, ?AiSettings $settings = null): void
    {
        $settings ??= $this->ai->settings();
        if (! $settings->enabled()) {
            throw new AiException('AI content is switched off in Settings › AI content.');
        }
        if (! $settings->hasKey()) {
            throw new AiException('Add an API key for '.AiSettings::PROVIDERS[$settings->provider()]['label'].' in Settings › AI content.');
        }
        if (! $settings->fields()) {
            throw new AiException('Choose at least one part of the page for AI to write in Settings › AI content.');
        }
        if (in_array($page->status, [ContentStatus::Published, ContentStatus::Scheduled], true)) {
            throw new AiException('AI only writes to drafts. Unpublish this page first.');
        }
        if (AiGeneration::pending()->where('subject_type', $page->getMorphClass())->where('subject_id', $page->getKey())->exists()) {
            throw new AiException('AI is already writing this page.');
        }
        if (($budget = $settings->monthlyBudget()) && $this->spentThisMonth() >= $budget) {
            throw new AiException('This month\'s AI budget of $'.number_format($budget, 2).' has been reached.');
        }
    }

    public function spentThisMonth(): float
    {
        return (float) AiGeneration::thisMonth()->sum('cost_usd');
    }

    /** Runs one queued generation (called from the job). Failures are recorded on the row, not thrown. */
    public function run(AiGeneration $generation): void
    {
        $page = LandingPage::with(['service', 'location', 'industry', 'seo'])->find($generation->subject_id);
        $started = microtime(true);
        $generation->update(['status' => 'running', 'started_at' => now()]);
        $settings = $this->ai->settings();

        try {
            if (! $page) {
                throw new AiException('The page no longer exists.');
            }
            if (in_array($page->status, [ContentStatus::Published, ContentStatus::Scheduled], true)) {
                throw new AiException('The page was published before AI finished, so nothing was changed.');
            }

            $fields = $generation->fields ?: $settings->fields();
            $prompt = new PseoPrompt(app(SettingsService::class), $settings);
            $response = $this->ai->driver($generation->provider, $generation->model)->generate(new AiRequest(
                system: $prompt->system(),
                prompt: $prompt->user($page, $fields),
                schema: PseoContentSchema::for($fields),
                schemaName: 'landing_page',
                maxTokens: $settings->maxTokens(),
                effort: $settings->effort(),
                temperature: $settings->temperature(),
            ));

            $content = $this->clean($response->data, $fields);
            if (! array_filter($content)) {
                throw new AiException('The AI returned an empty page.');
            }
            $faqIds = $this->apply($page, $content, $fields, $generation);
            [$similarity, $similarTo] = $this->similarity($page);

            $generation->update([
                'status' => 'succeeded',
                'model' => $response->model,
                'input_tokens' => $response->inputTokens,
                'output_tokens' => $response->outputTokens,
                'cost_usd' => $settings->cost($response->inputTokens, $response->outputTokens),
                'similarity' => $similarity,
                'output' => $content + ['faq_ids' => $faqIds, 'similar_to' => $similarTo],
                'duration_ms' => (int) ((microtime(true) - $started) * 1000),
                'finished_at' => now(),
            ]);
            ActivityLog::record('ai_generated', $page, 'AI wrote draft content ('.$generation->provider.' · '.$response->model.')', [
                'generation' => $generation->id, 'fields' => $fields, 'tokens' => $response->inputTokens + $response->outputTokens,
            ]);
        } catch (AiException $e) {
            $this->fail($generation, $e->getMessage(), $started);
        } catch (\Throwable $e) {
            report($e);
            $this->fail($generation, 'Unexpected error: '.Str::limit($e->getMessage(), 300), $started);
        }
    }

    public function fail(AiGeneration $generation, string $message, ?float $started = null): void
    {
        $generation->update([
            'status' => 'failed',
            'error' => Str::limit($message, 1000),
            'duration_ms' => $started ? (int) ((microtime(true) - $started) * 1000) : null,
            'finished_at' => now(),
        ]);
    }

    /** Type-checks, trims, sanitises and caps the model output. */
    public function clean(array $data, array $fields): array
    {
        $text = fn ($v, int $max = 255) => Str::limit(trim(preg_replace('/\s+/u', ' ', strip_tags((string) $v))), $max, '');
        $html = fn ($v) => $this->html((string) $v);
        $icon = fn ($v) => in_array($v, PseoContentSchema::icons(), true) ? $v : 'check-circle';
        $rows = fn (string $key, callable $map) => collect(is_array($data[$key] ?? null) ? $data[$key] : [])
            ->filter(fn ($r) => is_array($r))->map($map)
            ->filter(fn ($r) => collect($r)->except('icon')->every(fn ($v) => $v !== ''))
            ->take(self::LIMITS[$key])->values()->all();

        $out = [];
        if (in_array('hero', $fields, true)) {
            $out['h1'] = $text($data['h1'] ?? '');
            $out['hero_subheading'] = $text($data['hero_subheading'] ?? '');
            $out['introduction'] = $html($data['introduction'] ?? '');
        }
        if (in_array('content', $fields, true)) {
            $out['content'] = $html($data['content'] ?? '');
        }
        if (in_array('sections', $fields, true)) {
            $out['sections'] = $rows('sections', fn ($r) => ['heading' => $text($r['heading'] ?? ''), 'content' => $html($r['content'] ?? '')]);
        }
        foreach (['benefits', 'why_choose_us'] as $key) {
            if (in_array($key, $fields, true)) {
                $out[$key] = $rows($key, fn ($r) => ['title' => $text($r['title'] ?? ''), 'text' => $text($r['text'] ?? '', 600), 'icon' => $icon($r['icon'] ?? null)]);
            }
        }
        if (in_array('process', $fields, true)) {
            $out['process'] = $rows('process', fn ($r) => ['title' => $text($r['title'] ?? ''), 'text' => $text($r['text'] ?? '', 600)]);
        }
        if (in_array('faqs', $fields, true)) {
            $out['faqs'] = $rows('faqs', fn ($r) => ['question' => $text($r['question'] ?? '', 500), 'answer' => $html($r['answer'] ?? '')]);
        }
        if (in_array('seo', $fields, true)) {
            $out['meta_title'] = $text($data['meta_title'] ?? '');
            $out['meta_description'] = $text($data['meta_description'] ?? '', 500);
            $out['focus_keyword'] = Str::lower($text($data['focus_keyword'] ?? ''));
        }

        return $out;
    }

    /** @return int[] ids of the FAQs created for this page */
    private function apply(LandingPage $page, array $content, array $fields, AiGeneration $generation): array
    {
        return DB::transaction(function () use ($page, $content, $fields, $generation) {
            $attributes = collect($content)->only(['h1', 'hero_subheading', 'introduction', 'content', 'sections', 'benefits', 'process', 'why_choose_us'])
                ->reject(fn ($v) => $v === '' || $v === [])->all();
            $page->fill($attributes)->save();

            if (in_array('seo', $fields, true)) {
                // Indexing stays off: a person switches it on after reviewing the page.
                $page->syncSeo(array_filter([
                    'meta_title' => $content['meta_title'] ?: null,
                    'meta_description' => $content['meta_description'] ?: null,
                    'focus_keyword' => $content['focus_keyword'] ?: null,
                ]) + ['robots_index' => false, 'include_in_sitemap' => false]);
            }

            if (! in_array('faqs', $fields, true) || empty($content['faqs'])) {
                return [];
            }

            // Replace FAQs written by earlier AI runs for this page; keep any that were attached by hand.
            $previous = AiGeneration::where('subject_type', $page->getMorphClass())->where('subject_id', $page->getKey())
                ->where('status', 'succeeded')->whereKeyNot($generation->id)->get(['output'])
                ->flatMap(fn ($g) => $g->output['faq_ids'] ?? [])->unique()->all();
            $sharedElsewhere = DB::table('page_faqs')->whereIn('faq_id', $previous)
                ->where(fn ($q) => $q->where('faqable_type', '!=', $page->getMorphClass())->orWhere('faqable_id', '!=', $page->getKey()))
                ->pluck('faq_id')->all();
            Faq::whereIn('id', array_diff($previous, $sharedElsewhere))->delete();

            $newIds = collect($content['faqs'])->values()->map(fn ($f, $i) => Faq::create([
                'question' => $f['question'],
                'answer' => $f['answer'],
                'is_active' => true,
                'sort_order' => $i,
            ])->id)->all();

            $manual = $page->faqs()->pluck('faqs.id')->diff($previous)->all();
            $page->syncFaqs(array_merge($manual, $newIds));
            $page->update(['faq_enabled' => true]);

            return $newIds;
        });
    }

    /** Strips everything outside the content whitelist, then removes links (models shouldn't invent URLs). */
    private function html(string $html): string
    {
        if (trim(strip_tags($html)) === '') {
            return '';
        }
        $clean = (new LegacyHtml((string) parse_url((string) config('app.url'), PHP_URL_HOST)))->post($html);

        return trim(preg_replace('#</?a\b[^>]*>#i', '', $clean));
    }

    /**
     * Highest word-shingle overlap (Jaccard, 5-word shingles) with other pages of the same service,
     * so near-duplicate "doorway" pages are flagged before anyone publishes them.
     *
     * @return array{0: ?float, 1: ?int}
     */
    public function similarity(LandingPage $page): array
    {
        $mine = $this->shingles($page);
        if (! $mine) {
            return [null, null];
        }
        $best = [0.0, null];
        LandingPage::where('service_id', $page->service_id)->whereKeyNot($page->getKey())
            ->latest('updated_at')->limit(60)->get(['id', 'introduction', 'content', 'sections'])
            ->each(function ($other) use ($mine, &$best) {
                $theirs = $this->shingles($other);
                if (! $theirs) {
                    return;
                }
                $score = count(array_intersect_key($mine, $theirs)) / count($mine + $theirs);
                if ($score > $best[0]) {
                    $best = [$score, $other->id];
                }
            });

        return [round($best[0], 3), $best[1]];
    }

    private function shingles(LandingPage $page): array
    {
        $text = Str::lower(strip_tags(implode(' ', [$page->introduction, $page->content, collect($page->sections ?? [])->pluck('content')->implode(' ')])));
        $words = preg_split('/[^\p{L}\p{N}]+/u', $text, -1, PREG_SPLIT_NO_EMPTY);
        $set = [];
        for ($i = 0; $i + 5 <= count($words); $i++) {
            $set[implode(' ', array_slice($words, $i, 5))] = true;
        }

        return $set;
    }
}
