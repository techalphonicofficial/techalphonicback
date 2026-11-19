<?php

namespace Database\Seeders;

use App\Models\HeroSlide;
use App\Models\HomeSection;
use App\Models\Media;
use App\Services\MediaService;
use App\Services\SettingsService;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;

/**
 * Imports the homepage stock media (Pexels — free for commercial use, no attribution required) into the
 * Media Library and wires it to the hero, sections and popup. Everything stays replaceable in Admin.
 * Sources: video 7687973, photos 3182800, 7438096, 2102416, 7988116 on pexels.com.
 * live-marketing-dashboard.webp and clients/* (client logos) come from the old techalphonic.com website.
 */
class HomeMediaSeeder extends Seeder
{
    private const FILES = [
        'video' => ['hero-team.mp4', 'Team meeting in a modern office', 'Tech Alphonic team at work'],
        'poster' => ['hero-poster.jpg', 'Digital team reviewing growth analytics in a meeting', 'Hero background'],
        'about' => ['about-team.jpg', 'Tech Alphonic specialists collaborating on a client strategy', 'Our team'],
        'showreel' => ['showreel.jpg', 'Marketing and development team planning a project', 'How we work'],
        'promo' => ['live-marketing-dashboard.webp', 'Digital marketing dashboard with SEO, social and campaign performance', 'SEO plans'],
        'dev' => ['dev-pair.jpg', 'Two developers collaborating on code', 'Development team'],
    ];

    /** Client logos shown on the old website (file => client name). */
    private const CLIENTS = [
        'ayurgrid.jpg' => 'AyurGrid',
        'lawgical.png' => 'Lawgical',
        'kitchen-fix-solution-logo.png' => 'Kitchen Fix Solution',
        'desi-guru.png' => 'Desi Guru',
        'logo-main.png' => 'Chardham Yatra',
        'Services_Plus.webp' => 'Services Plus',
        'Com-Paint_Round_Logo.webp' => 'Com-Paint',
        'Enlive.webp' => 'Enlive',
    ];

    public function run(): void
    {
        if (app()->runningUnitTests()) {
            return; // don't copy 4 MB of media into storage on every test run
        }
        $media = app(MediaService::class);
        $ids = [];

        foreach (self::FILES as $key => [$file, $alt, $title]) {
            $existing = Media::where('original_name', $file)->first();
            if ($existing) {
                $ids[$key] = $existing->id;

                continue;
            }
            $path = database_path('seeders/media/'.$file);
            if (! is_file($path)) {
                continue;
            }
            $upload = new UploadedFile($path, $file, mime_content_type($path), null, true);
            $ids[$key] = $media->store($upload, ['alt' => $alt, 'title' => $title, 'folder' => 'home'])->id;
        }

        if ($hero = HeroSlide::orderBy('sort_order')->first()) {
            $hero->update(array_filter([
                'background_video_id' => $ids['video'] ?? null,
                'background_id' => $ids['poster'] ?? null,
            ]));
        }

        if (isset($ids['promo']) && ($promo = HomeSection::where('key', 'promo')->first())) {
            $promo->update(['content' => array_merge($promo->content ?? [], ['image_id' => $ids['promo']])]);
        }
        $this->clients($media);

        $sectionImages = ['about' => 'about', 'showreel' => 'showreel', 'services' => 'dev'];
        foreach ($sectionImages as $sectionKey => $mediaKey) {
            $section = HomeSection::where('key', $sectionKey)->first();
            if ($section && isset($ids[$mediaKey]) && empty($section->content['image_id'])) {
                $section->update(['content' => array_merge($section->content ?? [], ['image_id' => $ids[$mediaKey]])]);
            }
        }
        if ($showreel = HomeSection::where('key', 'showreel')->first()) {
            if (isset($ids['video']) && empty($showreel->content['video_id'])) {
                $showreel->update(['content' => array_merge($showreel->content ?? [], ['video_id' => $ids['video']])]);
            }
        }

        if (isset($ids['about'])) {
            app(SettingsService::class)->set('popup_image', $ids['about'], 'popup');
        }
    }

    private function clients(MediaService $media): void
    {
        $items = [];
        foreach (self::CLIENTS as $file => $name) {
            $path = database_path('seeders/media/clients/'.$file);
            $id = Media::where('original_name', $file)->value('id');
            if (! $id && is_file($path)) {
                $upload = new UploadedFile($path, $file, mime_content_type($path), null, true);
                $id = $media->store($upload, ['alt' => $name.' logo', 'title' => $name, 'folder' => 'clients'])->id;
            }
            $items[] = ['name' => $name, 'logo_id' => $id];
        }
        if ($section = HomeSection::where('key', 'clients')->first()) {
            $section->update(['content' => array_merge($section->content ?? [], ['items' => $items])]);
        }
    }
}
