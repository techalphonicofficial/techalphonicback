<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\HeroSlideRequest;
use App\Models\ActivityLog;
use App\Models\Faq;
use App\Models\HeroSlide;
use App\Models\HomeSection;
use App\Models\Media;
use App\Support\Icons;
use App\Support\SitePresenter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Homepage CMS: hero + reorderable/toggleable sections, each with its own content fields.
 */
class HomeController extends Controller
{
    public function edit(Request $request)
    {
        abort_unless($request->user()->can('pages.update'), 403);

        foreach (array_keys(HomeSection::KEYS) as $i => $key) {
            HomeSection::firstOrCreate(['key' => $key], ['sort_order' => $i, 'is_visible' => false]);
        }

        $sections = HomeSection::orderBy('sort_order')->get();
        $sectionMediaIds = $sections->flatMap(fn ($sec) => SitePresenter::mediaIds($sec->content ?? []))->unique()->values()->all();
        $mediaThumbs = $sectionMediaIds
            ? Media::whereIn('id', $sectionMediaIds)->get()->mapWithKeys(fn ($m) => [$m->id => $m->webp_url ?: $m->url])->all()
            : [];

        return view('admin.home.edit', [
            'sections' => $sections,
            'mediaThumbs' => $mediaThumbs,
            'hero' => HeroSlide::with(['image', 'background', 'backgroundVideo'])->orderBy('sort_order')->first() ?? new HeroSlide(['is_active' => true, 'show_form' => true]),
            'faqs' => Faq::active()->orderBy('question')->get(['id', 'question']),
            'icons' => Icons::names(),
        ]);
    }

    public function updateHero(HeroSlideRequest $request)
    {
        $hero = HeroSlide::orderBy('sort_order')->first() ?? new HeroSlide;
        $hero->fill($request->validated())->save();
        ActivityLog::record('hero_updated', $hero, 'Homepage hero updated');

        return back()->with('success', 'Hero saved.')->withFragment('hero');
    }

    public function updateSections(Request $request)
    {
        abort_unless($request->user()->can('pages.update'), 403);
        $data = $request->validate([
            'sections' => ['required', 'array'],
            'sections.*.title' => ['nullable', 'string', 'max:255'],
            'sections.*.subtitle' => ['nullable', 'string', 'max:500'],
            'sections.*.eyebrow' => ['nullable', 'string', 'max:100'],
            'sections.*.is_visible' => ['nullable', 'boolean'],
            'sections.*.sort_order' => ['required', 'integer', 'min:0'],
            'sections.*.content' => ['nullable', 'json'],
        ]);

        DB::transaction(function () use ($data) {
            foreach ($data['sections'] as $key => $row) {
                $section = HomeSection::where('key', $key)->first();
                if (! $section) {
                    continue;
                }
                $content = isset($row['content']) ? (json_decode($row['content'], true) ?: null) : $section->content;
                if ($key === 'clients' && is_array($content['items'] ?? null)) {
                    $content['items'] = array_map(function ($item) {
                        if (! is_array($item)) {
                            return $item;
                        }
                        $item['url'] = $this->normalizeClientUrl($item['url'] ?? null);

                        return $item;
                    }, $content['items']);
                }
                $section->update([
                    'title' => $row['title'] ?? null,
                    'subtitle' => $row['subtitle'] ?? null,
                    'eyebrow' => $row['eyebrow'] ?? null,
                    'is_visible' => (bool) ($row['is_visible'] ?? false),
                    'sort_order' => $row['sort_order'],
                    'content' => $content,
                ]);
            }
        });
        ActivityLog::record('home_sections_updated', null, 'Homepage sections updated');

        return back()->with('success', 'Homepage sections saved.');
    }

    private function normalizeClientUrl(?string $raw): ?string
    {
        $url = trim((string) $raw);
        if ($url === '') {
            return null;
        }
        if (! preg_match('~^(https?://|/|#|mailto:|tel:)~i', $url)) {
            $url = 'https://'.$url;
        }

        return $url;
    }
}
