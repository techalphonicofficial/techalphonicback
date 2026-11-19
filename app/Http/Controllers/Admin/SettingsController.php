<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\SocialLink;
use App\Services\SettingsService;
use App\Support\ContentCache;
use App\Support\Icons;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SettingsController extends Controller
{
    public function __construct(private SettingsService $settings) {}

    public function edit(Request $request, string $group = 'general')
    {
        abort_unless($request->user()->can('settings.manage'), 403);
        abort_unless(isset(SettingsService::FIELDS[$group]) || $group === 'social', 404);

        return view('admin.settings.edit', [
            'group' => $group,
            'groups' => array_keys(SettingsService::FIELDS) + [99 => 'social'],
            'fields' => SettingsService::FIELDS[$group] ?? [],
            'values' => $this->settings->all(),
            'socials' => $group === 'social' ? SocialLink::orderBy('sort_order')->get() : collect(),
            'icons' => Icons::names(),
            'mail' => [
                'mailer' => config('mail.default'),
                'host' => config('mail.mailers.smtp.host'),
                'port' => config('mail.mailers.smtp.port'),
                'from' => config('mail.from.address'),
            ],
        ]);
    }

    public function update(Request $request, string $group)
    {
        abort_unless($request->user()->can('settings.manage'), 403);
        $fields = SettingsService::FIELDS[$group] ?? abort(404);

        $rules = [];
        foreach ($fields as $key => $def) {
            $rules[$key] = match ($def[1]) {
                'email' => ['nullable', 'email', 'max:150'],
                'url' => ['nullable', 'url', 'max:500'],
                'image' => ['nullable', 'integer', 'exists:media,id'],
                'boolean' => ['nullable', 'boolean'],
                'color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
                'number' => ['nullable', 'integer'],
                'select' => ['nullable', Rule::in(array_keys($def[3] ?? []))],
                'code' => ['nullable', 'string', 'max:20000'],
                default => ['nullable', 'string', 'max:2000'],
            };
        }
        // Tracking IDs must look like tracking IDs (prevents script injection through ID fields).
        $rules['ga4_id'] = ['nullable', 'regex:/^G-[A-Z0-9]{4,15}$/'];
        $rules['gtm_id'] = ['nullable', 'regex:/^GTM-[A-Z0-9]{4,12}$/'];
        $rules['meta_pixel_id'] = ['nullable', 'regex:/^\d{6,20}$/'];
        $rules['gsc_verification'] = ['nullable', 'regex:/^[A-Za-z0-9_\-]{10,100}$/'];
        $rules['whatsapp_number'] = ['nullable', 'regex:/^\+?\d{8,15}$/'];
        if (in_array($group, ['analytics'], true) && ! $request->user()->hasRole('Super Admin')) {
            // Raw script fields are restricted to Super Admins.
            unset($rules['head_scripts'], $rules['body_scripts']);
        }

        $data = $request->validate(array_intersect_key($rules, $fields));

        DB::transaction(function () use ($fields, $data, $group, $request) {
            foreach ($fields as $key => $def) {
                if (! array_key_exists($key, $data) && $def[1] !== 'boolean') {
                    continue;
                }
                $value = $def[1] === 'boolean' ? ($request->boolean($key) ? '1' : '0') : ($data[$key] ?? null);
                $this->settings->set($key, $value, $group);
            }
        });
        $this->settings->flush();
        ActivityLog::record('settings_updated', null, ucfirst($group).' settings updated', ['keys' => array_keys($data)]);

        return back()->with('success', 'Settings saved.');
    }

    public function updateSocial(Request $request)
    {
        abort_unless($request->user()->can('settings.manage'), 403);
        $data = $request->validate([
            'links' => ['nullable', 'array', 'max:20'],
            'links.*.platform' => ['required', 'string', 'max:50'],
            'links.*.url' => ['required', 'url', 'max:255'],
            'links.*.icon' => ['nullable', 'string', 'max:50'],
            'links.*.is_active' => ['nullable', 'boolean'],
        ]);

        DB::transaction(function () use ($data) {
            SocialLink::query()->delete();
            foreach (array_values($data['links'] ?? []) as $i => $l) {
                SocialLink::create($l + ['sort_order' => $i, 'is_active' => (bool) ($l['is_active'] ?? true)]);
            }
        });
        ContentCache::flush();
        ActivityLog::record('social_links_updated');

        return back()->with('success', 'Social links saved.');
    }
}
