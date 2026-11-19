<?php

namespace Tests\Feature;

use App\Ai\AiException;
use App\Ai\AiManager;
use App\Ai\AiRequest;
use App\Ai\AiSettings;
use App\Ai\Drivers\GeminiDriver;
use App\Ai\Drivers\OpenAiDriver;
use App\Ai\Pseo\PseoContentSchema;
use App\Enums\ContentStatus;
use App\Models\AiGeneration;
use App\Models\Faq;
use App\Models\LandingPage;
use App\Models\Setting;
use App\Services\SettingsService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiContentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    private function configure(array $overrides = []): AiSettings
    {
        $s = new AiSettings;
        foreach ($overrides + ['ai_enabled' => '1', 'ai_provider' => 'anthropic', 'ai_price_input' => '5', 'ai_price_output' => '25'] as $k => $v) {
            $s->set($k, $v);
        }
        $s->setApiKey('anthropic', 'sk-ant-test-1234567890');

        return $s;
    }

    private function aiOutput(array $overrides = []): array
    {
        return $overrides + [
            'h1' => 'SEO Services for Growing Noida Businesses',
            'hero_subheading' => 'Local search, done properly',
            'introduction' => '<p>Intro about Noida.</p><script>alert(1)</script>',
            'content' => '<h1>Big</h1><h2>Why local SEO</h2><p>Body with <a href="https://evil.test">a link</a> and <strong>bold</strong>.</p><img src=x onerror=alert(1)>',
            'sections' => [['heading' => 'Noida market', 'content' => '<p>Section text.</p>'], ['heading' => '', 'content' => '<p>dropped</p>']],
            'benefits' => [['title' => 'More leads', 'text' => 'Reach buyers.', 'icon' => 'not-an-icon']],
            'process' => [['title' => 'Audit', 'text' => 'We review.']],
            'why_choose_us' => [['title' => 'Senior team', 'text' => 'Experienced.', 'icon' => 'users']],
            'faqs' => [['question' => 'How long does SEO take?', 'answer' => '<p>Usually months.</p>'], ['question' => 'Do you report?', 'answer' => '<p>Monthly.</p>']],
            'meta_title' => 'SEO Company in Noida | Tech Alphonic',
            'meta_description' => 'Grow in Noida with SEO that brings qualified leads.',
            'focus_keyword' => 'SEO Company in Noida',
        ];
    }

    private function draftPage(): LandingPage
    {
        return LandingPage::with('seo')->where('status', ContentStatus::Draft->value)->firstOrFail();
    }

    public function test_settings_require_ai_configure_permission(): void
    {
        $this->actingAs($this->admin('Editor'))->get(route('admin.ai.settings'))->assertForbidden();
        $this->actingAs($this->admin('Admin'))->get(route('admin.ai.settings'))->assertOk()->assertSee('Test connection');
    }

    public function test_api_keys_are_encrypted_never_echoed_and_kept_when_left_blank(): void
    {
        $admin = $this->admin('Admin');
        $form = [
            'ai_enabled' => '1', 'ai_provider' => 'openai', 'ai_effort' => 'medium', 'ai_temperature' => '0.7', 'ai_max_tokens' => '16000',
            'ai_fields' => ['hero', 'faqs'], 'ai_openai_model' => 'gpt-5-mini', 'ai_openai_key' => 'sk-proj-SECRETVALUE9876',
        ];
        $this->actingAs($admin)->put(route('admin.ai.settings.update'), $form)->assertSessionHas('success');

        $stored = Setting::where('key', 'ai_openai_key')->value('value');
        $this->assertNotEmpty($stored);
        $this->assertStringNotContainsString('SECRETVALUE', $stored);
        $this->assertSame('sk-proj-SECRETVALUE9876', (new AiSettings)->apiKey('openai'));
        $this->assertArrayNotHasKey('ai_openai_key', app(SettingsService::class)->all());

        $html = $this->actingAs($admin)->get(route('admin.ai.settings'))->assertOk()->getContent();
        $this->assertStringNotContainsString('SECRETVALUE', $html);
        $this->assertStringContainsString('••••9876', $html);

        // Blank key field keeps the saved key; the remove box clears it.
        $this->actingAs($admin)->put(route('admin.ai.settings.update'), ['ai_openai_key' => ''] + $form)->assertSessionHas('success');
        $this->assertSame('sk-proj-SECRETVALUE9876', (new AiSettings)->apiKey('openai'));
        $this->actingAs($admin)->put(route('admin.ai.settings.update'), ['ai_openai_key' => '', 'ai_openai_key_remove' => '1'] + $form);
        $this->assertNull((new AiSettings)->apiKey('openai'));
    }

    public function test_write_with_ai_applies_clean_content_straight_to_the_draft(): void
    {
        $this->configure();
        $fake = app(AiManager::class)->fake($this->aiOutput());
        $page = $this->draftPage();
        $manualFaq = Faq::create(['question' => 'Manual question?', 'answer' => '<p>Kept.</p>', 'is_active' => true]);
        $page->syncFaqs([$manualFaq->id]);

        $this->actingAs($this->admin('SEO Manager'))->post(route('admin.landing-pages.ai', $page->id))->assertSessionHas('success');

        $page->refresh()->load('seo', 'faqs');
        $g = AiGeneration::latest('id')->first();
        $this->assertSame('succeeded', $g->status, (string) $g->error);
        $this->assertSame(ContentStatus::Draft, $page->status);
        $this->assertSame('SEO Services for Growing Noida Businesses', $page->h1);
        $this->assertStringNotContainsString('<script', $page->introduction);
        $this->assertStringNotContainsString('<h1', $page->content);
        $this->assertStringNotContainsString('<a ', $page->content);
        $this->assertStringNotContainsString('<img', $page->content);
        $this->assertStringContainsString('<strong>bold</strong>', $page->content);
        $this->assertCount(1, $page->sections);
        $this->assertSame('check-circle', $page->benefits[0]['icon']);
        $this->assertSame('seo company in noida', $page->seo->focus_keyword);
        $this->assertFalse((bool) $page->seo->robots_index);
        $this->assertEqualsCanonicalizing(['Manual question?', 'How long does SEO take?', 'Do you report?'], $page->faqs->pluck('question')->all());
        $this->assertSame(4600, $g->totalTokens());
        $this->assertEqualsWithDelta((1200 * 5 + 3400 * 25) / 1e6, $g->cost_usd, 0.00001);

        // The prompt is grounded in the page's own records and uses the strict schema.
        $request = $fake->requests[0];
        $this->assertStringContainsString($page->service->name, $request->prompt);
        $this->assertFalse($request->schema['additionalProperties']);
        $this->assertSame(array_keys($request->schema['properties']), $request->schema['required']);

        // Rewriting replaces the AI FAQs from the previous run but keeps the manual one.
        $oldAiFaqIds = $g->output['faq_ids'];
        app(AiManager::class)->fake($this->aiOutput(['faqs' => [['question' => 'New question?', 'answer' => '<p>New.</p>']]]));
        $this->actingAs($this->admin('SEO Manager'))->post(route('admin.landing-pages.ai', $page->id));
        $this->assertEqualsCanonicalizing(['Manual question?', 'New question?'], $page->fresh()->faqs->pluck('question')->all());
        $this->assertSame(0, Faq::whereIn('id', $oldAiFaqIds)->count());
    }

    public function test_live_pages_disabled_ai_and_budget_are_refused(): void
    {
        $this->configure(['ai_monthly_budget' => '1']);
        app(AiManager::class)->fake($this->aiOutput());
        $user = $this->admin('SEO Manager');

        $live = LandingPage::where('status', ContentStatus::Published->value)->firstOrFail();
        $this->actingAs($user)->post(route('admin.landing-pages.ai', $live->id))->assertSessionHas('error');

        $page = $this->draftPage();
        AiGeneration::create(['subject_type' => 'landing_page', 'subject_id' => $page->id, 'provider' => 'anthropic', 'model' => 'x',
            'prompt_version' => 'v', 'status' => 'succeeded', 'cost_usd' => 1.5]);
        $this->actingAs($user)->post(route('admin.landing-pages.ai', $page->id))->assertSessionHas('error', fn ($m) => str_contains($m, 'budget'));

        (new AiSettings)->set('ai_enabled', '0');
        $this->actingAs($user)->post(route('admin.landing-pages.ai', $page->id))->assertSessionHas('error', fn ($m) => str_contains($m, 'switched off'));
        $this->assertSame(1, AiGeneration::count());

        $this->actingAs($this->admin('Sales'))->post(route('admin.landing-pages.ai', $page->id))->assertForbidden();
    }

    public function test_provider_errors_are_recorded_and_the_page_is_left_untouched(): void
    {
        $this->configure();
        app(AiManager::class)->fake(new AiException('Anthropic rejected the API key.'));
        $page = $this->draftPage();
        $before = $page->introduction;

        $this->actingAs($this->admin('SEO Manager'))->post(route('admin.landing-pages.ai', $page->id));

        $g = AiGeneration::latest('id')->first();
        $this->assertSame('failed', $g->status);
        $this->assertSame('Anthropic rejected the API key.', $g->error);
        $this->assertSame($before, $page->fresh()->introduction);
        $this->actingAs($this->admin('SEO Manager'))->getJson(route('admin.landing-pages.ai-status', $page->id))
            ->assertJson(['status' => 'failed', 'pending' => false]);
    }

    public function test_bulk_action_queues_ai_for_drafts_and_skips_live_pages(): void
    {
        $this->configure();
        app(AiManager::class)->fake($this->aiOutput());
        $ids = LandingPage::pluck('id')->all();
        $drafts = LandingPage::whereNotIn('status', ['published', 'scheduled'])->count();

        $this->actingAs($this->admin('SEO Manager'))
            ->post(route('admin.landing-pages.bulk-action'), ['ids' => $ids, 'bulk_action' => 'ai_write'])
            ->assertRedirectContains('/ai/generations');

        $this->assertSame($drafts, AiGeneration::where('status', 'succeeded')->count());
        $this->actingAs($this->admin('SEO Manager'))->get(route('admin.ai.generations'))->assertOk()->assertSee('Applied');
    }

    public function test_openai_driver_sends_strict_schema_and_parses_output(): void
    {
        Http::fake(['api.openai.com/v1/responses' => Http::response([
            'status' => 'completed', 'model' => 'gpt-5-2026',
            'output' => [['type' => 'reasoning'], ['type' => 'message', 'content' => [['type' => 'output_text', 'text' => '{"h1":"Hi"}']]]],
            'usage' => ['input_tokens' => 10, 'output_tokens' => 20],
        ])]);

        $res = (new OpenAiDriver('sk-test', 'gpt-5'))->generate(new AiRequest('sys', 'user', PseoContentSchema::for(['hero'])));

        $this->assertSame(['h1' => 'Hi'], $res->data);
        $this->assertSame(30, $res->inputTokens + $res->outputTokens);
        Http::assertSent(fn ($r) => $r['text']['format']['strict'] === true && $r['reasoning']['effort'] === 'medium'
            && ! isset($r['temperature']) && $r->hasHeader('Authorization', 'Bearer sk-test'));
    }

    public function test_gemini_driver_sends_key_in_header_and_reports_truncation(): void
    {
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [['finishReason' => 'MAX_TOKENS', 'content' => ['parts' => [['text' => '{"h1":']]]]],
        ])]);

        try {
            (new GeminiDriver('g-key', 'gemini-2.5-pro'))->generate(new AiRequest('sys', 'user', PseoContentSchema::for(['hero'])));
            $this->fail('Expected an AiException');
        } catch (AiException $e) {
            $this->assertStringContainsString('max-token', $e->getMessage());
        }
        Http::assertSent(fn ($r) => $r->hasHeader('x-goog-api-key', 'g-key') && ! str_contains($r->url(), 'g-key')
            && $r['generationConfig']['responseMimeType'] === 'application/json');
    }

    public function test_connection_test_endpoint_reports_errors_without_leaking_the_key(): void
    {
        Http::fake(['api.openai.com/*' => Http::response(['error' => ['message' => 'Incorrect API key provided']], 401)]);

        $this->actingAs($this->admin('Admin'))
            ->postJson(route('admin.ai.settings.test'), ['provider' => 'openai', 'model' => 'gpt-5', 'key' => 'sk-wrong-123456'])
            ->assertOk()
            ->assertJson(['ok' => false])
            ->assertDontSee('sk-wrong-123456');
    }
}
