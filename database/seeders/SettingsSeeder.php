<?php

namespace Database\Seeders;

use App\Models\Media;
use App\Models\SocialLink;
use App\Services\SettingsService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = app(SettingsService::class);

        $values = [
            // general
            'site_name' => 'Tech Alphonic',
            'site_tagline' => 'Your Trusted IT Partner',
            'primary_color' => '#167B8B', // logo teal (deepened for contrast); logo orange is used as a small accent
            'secondary_color' => '#53AFBE',
            'accent_color' => '#FEB449',
            // contact (business details supplied by the client)
            'phone' => '+91 98991 84918',
            'email' => 'info@techalphonic.com',
            'address_line' => 'C-203, C Block, Sector 63, Hazratpur Wajidpur',
            'address_locality' => 'Noida',
            'address_region' => 'Uttar Pradesh',
            'postal_code' => '201309',
            'country' => 'India',
            'country_code' => 'IN',
            'latitude' => '28.6273',
            'longitude' => '77.3787',
            'business_hours' => "Mon – Sat: 10:00 AM – 7:00 PM\nSunday: Closed",
            'opening_hours_schema' => 'Mo-Sa 10:00-19:00',
            'map_embed_url' => 'https://www.google.com/maps?q=C-203+C+Block+Sector+63+Noida+Uttar+Pradesh+201309&output=embed',
            // whatsapp / call
            'whatsapp_enabled' => '1',
            'whatsapp_number' => '919899184918',
            'whatsapp_message' => 'Hi Tech Alphonic, I would like to discuss a project.',
            'whatsapp_position' => 'right',
            'call_enabled' => '1',
            'call_text' => 'Call Now',
            // seo
            'default_meta_title' => 'Tech Alphonic | SEO, Digital Marketing & Web Development Company in Noida',
            'title_separator' => '|',
            'default_meta_description' => 'Tech Alphonic is a Noida-based digital agency offering SEO, performance marketing, website and app development that drive measurable business growth.',
            'organization_type' => 'ProfessionalService',
            'price_range' => '₹₹',
            // forms
            'lead_notify_email' => 'info@techalphonic.com',
            'lead_autoreply_enabled' => '1',
            'lead_success_message' => 'Thank you! Our team will get back to you within one business day.',
            // footer
            'footer_about' => 'At Tech Alphonic, we help businesses build a solid digital footprint and achieve measurable growth with our AI-powered digital marketing, SEO, web, and mobile app development solutions.',
            'footer_copyright' => '© {year} {site_name}. All rights reserved.',
            'footer_cta_heading' => 'Ready to Grow Your Business With the Best IT Partner?',
            'footer_cta_text' => 'Give us a call or drop by anytime — we endeavour to answer all enquiries within 24 hours on business days.',
        ];

        foreach ($values as $key => $value) {
            $settings->set($key, $value);
        }

        // Logo supplied by the client (docs/brand). Copied into the media library so it is editable in Admin.
        $source = base_path('../docs/brand/tech-alphonic-logo.png');
        if (File::exists($source) && ! Media::where('filename', 'tech-alphonic-logo.png')->exists()) {
            Storage::disk('public')->put('brand/tech-alphonic-logo.png', File::get($source));
            [$w, $h] = getimagesize($source) ?: [null, null];
            $logo = Media::create([
                'disk' => 'public', 'path' => 'brand/tech-alphonic-logo.png', 'filename' => 'tech-alphonic-logo.png',
                'original_name' => 'tech-alphonic-logo.png', 'mime_type' => 'image/png', 'size' => File::size($source),
                'width' => $w, 'height' => $h, 'alt' => 'Tech Alphonic — Your Trusted IT Partner', 'title' => 'Tech Alphonic logo', 'folder' => 'brand',
            ]);
            $settings->set('logo', $logo->id);
            $settings->set('default_og_image', $logo->id);
        }

        foreach ([
            ['Facebook', 'https://www.facebook.com/techalphonic', 'facebook'],
            ['LinkedIn', 'https://www.linkedin.com/company/tech-alphonic', 'linkedin'],
            ['Instagram', 'https://www.instagram.com/techalphonic/', 'instagram'],
            ['X', 'https://x.com/techalphonic', 'twitter'],
            ['YouTube', 'https://www.youtube.com/channel/UCN93MjsN0XT1JU6M4kDULTA', 'youtube'],
        ] as $i => [$platform, $url, $icon]) {
            // Official profiles, as linked from the old website footer.
            SocialLink::updateOrCreate(['platform' => $platform], ['url' => $url, 'icon' => $icon, 'sort_order' => $i, 'is_active' => true]);
        }

        $settings->flush();
    }
}
