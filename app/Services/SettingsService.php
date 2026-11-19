<?php

namespace App\Services;

use App\Models\Media;
use App\Models\Setting;
use App\Support\ContentCache;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * Cached key/value site settings. Every business detail (name, phone, colours, tracking IDs…) lives here,
 * never in Blade templates.
 */
class SettingsService
{
    private const CACHE_KEY = 'settings.all';

    private ?array $loaded = null;

    /**
     * Admin form definition: group => [key => [label, type, help]].
     * Types: text, textarea, email, url, image, boolean, color, number, code, select
     */
    public const FIELDS = [
        'general' => [
            'site_name' => ['Website name', 'text'],
            'site_tagline' => ['Tagline', 'text'],
            'logo' => ['Logo', 'image'],
            'logo_dark' => ['Logo for dark backgrounds', 'image'],
            'favicon' => ['Favicon', 'image'],
            'primary_color' => ['Primary colour', 'color'],
            'secondary_color' => ['Secondary colour', 'color'],
            'accent_color' => ['Accent colour', 'color'],
            'founding_year' => ['Founding year', 'number'],
        ],
        'contact' => [
            'phone' => ['Phone', 'text'],
            'phone_secondary' => ['Secondary phone', 'text'],
            'email' => ['Email', 'email'],
            'sales_email' => ['Sales email', 'email'],
            'address_line' => ['Street address', 'text'],
            'address_locality' => ['City', 'text'],
            'address_region' => ['State', 'text'],
            'postal_code' => ['PIN / postal code', 'text'],
            'country' => ['Country', 'text'],
            'country_code' => ['Country code (ISO)', 'text'],
            'latitude' => ['Latitude', 'text'],
            'longitude' => ['Longitude', 'text'],
            'map_embed_url' => ['Google Maps embed URL', 'url', 'Paste the src="" value from Google Maps → Share → Embed'],
            'business_hours' => ['Business hours', 'textarea', 'One line per row, e.g. "Mon – Sat: 10:00 AM – 7:00 PM"'],
            'opening_hours_schema' => ['Opening hours (schema)', 'text', 'e.g. Mo-Sa 10:00-19:00'],
        ],
        'whatsapp' => [
            'whatsapp_enabled' => ['Show WhatsApp button', 'boolean'],
            'whatsapp_number' => ['WhatsApp number (with country code, digits only)', 'text'],
            'whatsapp_message' => ['Default message', 'text'],
            'whatsapp_position' => ['Position', 'select', null, ['right' => 'Bottom right', 'left' => 'Bottom left']],
            'call_enabled' => ['Show mobile call button', 'boolean'],
            'call_number' => ['Call button number', 'text', 'Leave empty to use the main phone number from Contact settings.'],
            'call_text' => ['Call button text', 'text'],
        ],
        'seo' => [
            'default_meta_title' => ['Default meta title', 'text'],
            'title_separator' => ['Title separator', 'text'],
            'default_meta_description' => ['Default meta description', 'textarea'],
            'default_og_image' => ['Default social share image', 'image'],
            'twitter_handle' => ['Twitter / X handle', 'text'],
            'robots_txt' => ['robots.txt content', 'code', 'Use {sitemap} to insert the sitemap URL'],
            'discourage_indexing' => ['Discourage search engines (staging)', 'boolean'],
            'organization_type' => ['Organization schema type', 'select', null, ['Organization' => 'Organization', 'LocalBusiness' => 'LocalBusiness', 'ProfessionalService' => 'ProfessionalService']],
            'price_range' => ['Price range (LocalBusiness)', 'text'],
        ],
        'analytics' => [
            'ga4_id' => ['Google Analytics 4 ID', 'text', 'G-XXXXXXX'],
            'gtm_id' => ['Google Tag Manager ID', 'text', 'GTM-XXXXXX'],
            'gsc_verification' => ['Search Console verification code', 'text'],
            'meta_pixel_id' => ['Meta Pixel ID', 'text'],
            'head_scripts' => ['Extra <head> code', 'code'],
            'body_scripts' => ['Extra code before </body>', 'code'],
        ],
        'forms' => [
            'lead_notify_email' => ['Send new lead alerts to', 'email'],
            'lead_autoreply_enabled' => ['Send confirmation email to customer', 'boolean'],
            'lead_success_message' => ['Form success message', 'text'],
            'recaptcha_enabled' => ['Enable Google reCAPTCHA v3', 'boolean', 'Keys are read from .env (RECAPTCHA_SITE_KEY / RECAPTCHA_SECRET_KEY)'],
            'budget_options' => ['Budget options (one per line)', 'textarea'],
        ],
        'popup' => [
            'popup_enabled' => ['Show enquiry popup automatically', 'boolean', 'The popup always opens from “Get a quote” buttons; this controls the automatic opening.'],
            'popup_delay' => ['Open after (seconds)', 'number', 'Also opens on exit-intent (desktop). Shown at most once per visit and never after the visitor has enquired.'],
            'popup_badge' => ['Badge text', 'text'],
            'popup_title' => ['Popup heading', 'text'],
            'popup_text' => ['Popup text', 'textarea'],
            'popup_points' => ['Benefit points (one per line)', 'textarea'],
            'popup_image' => ['Popup side image', 'image'],
            'quote_tab_enabled' => ['Show “Free Quote” side tab (desktop)', 'boolean'],
        ],
        'footer' => [
            'footer_about' => ['Footer about text', 'textarea'],
            'footer_copyright' => ['Copyright text', 'text', '{year} and {site_name} are replaced automatically'],
            'footer_cta_heading' => ['Footer CTA heading', 'text'],
            'footer_cta_text' => ['Footer CTA text', 'text'],
        ],
    ];

    public const DEFAULTS = [
        'site_name' => 'Tech Alphonic',
        'site_tagline' => 'Your Trusted IT Partner',
        'primary_color' => '#167B8B',
        'secondary_color' => '#53AFBE',
        'accent_color' => '#FEB449',
        'title_separator' => '|',
        'whatsapp_enabled' => '1',
        'whatsapp_position' => 'right',
        'call_enabled' => '1',
        'call_text' => 'Call Now',
        'lead_autoreply_enabled' => '1',
        'lead_success_message' => 'Thank you! Our team will get back to you within one business day.',
        'organization_type' => 'ProfessionalService',
        'country' => 'India',
        'country_code' => 'IN',
        // No admin path here: it is configurable (ADMIN_PATH), already noindex, and robots.txt would publish it.
        'robots_txt' => "User-agent: *\nAllow: /\nDisallow: /api/\nDisallow: /preview/\nDisallow: /search\n\nSitemap: {sitemap}",
        'footer_copyright' => '© {year} {site_name}. All rights reserved.',
        'budget_options' => "Under ₹25,000\n₹25,000 – ₹1,00,000\n₹1,00,000 – ₹5,00,000\n₹5,00,000+",
        'popup_enabled' => '1',
        'popup_delay' => '25',
        'popup_badge' => 'Free consultation',
        'popup_title' => 'Get a free growth plan for your business',
        'popup_text' => 'Tell us what you need — a specialist will call you back within one business day.',
        'popup_points' => "Free website & SEO audit\nCustom strategy & clear pricing\nNo obligation, no spam",
        'quote_tab_enabled' => '1',
    ];

    public function all(): array
    {
        if ($this->loaded !== null) {
            return $this->loaded;
        }

        $stored = Cache::rememberForever(self::CACHE_KEY, function () {
            if (! Schema::hasTable('settings')) {
                return [];
            }

            // The "ai" group (encrypted API keys) is read only by App\Ai\AiSettings and never cached here.
            return Setting::query()->where('group', '!=', 'ai')->pluck('value', 'key')->all();
        });

        return $this->loaded = array_merge(self::DEFAULTS, array_filter($stored, fn ($v) => $v !== null && $v !== ''));
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->all()[$key] ?? $default;
    }

    public function bool(string $key): bool
    {
        return filter_var($this->get($key, false), FILTER_VALIDATE_BOOLEAN);
    }

    public function media(string $key): ?Media
    {
        $id = $this->get($key);

        return $id ? Cache::remember("settings.media.$key.$id", 3600, fn () => Media::find($id)) : null;
    }

    public function set(string $key, mixed $value, ?string $group = null): void
    {
        $group ??= collect(self::FIELDS)->search(fn ($fields) => array_key_exists($key, $fields)) ?: 'general';
        Setting::updateOrCreate(['group' => $group, 'key' => $key], ['value' => is_array($value) ? json_encode($value) : $value]);
    }

    public function flush(): void
    {
        $this->loaded = null;
        Cache::forget(self::CACHE_KEY);
        ContentCache::flush();
    }

    public function whatsappUrl(?string $message = null): ?string
    {
        $number = preg_replace('/\D/', '', (string) $this->get('whatsapp_number', $this->get('phone')));
        if (! $number) {
            return null;
        }

        return 'https://wa.me/'.$number.'?text='.rawurlencode($message ?? (string) $this->get('whatsapp_message', ''));
    }

    public function telHref(?string $number = null): string
    {
        return 'tel:'.preg_replace('/[^\d+]/', '', (string) ($number ?? $this->get('call_number', $this->get('phone'))));
    }

    public function fullAddress(): string
    {
        return collect([
            $this->get('address_line'), $this->get('address_locality'), $this->get('address_region'),
            $this->get('postal_code'), $this->get('country'),
        ])->filter()->implode(', ');
    }

    public function replaceTokens(string $text): string
    {
        return strtr($text, ['{year}' => now()->year, '{site_name}' => $this->get('site_name')]);
    }
}
