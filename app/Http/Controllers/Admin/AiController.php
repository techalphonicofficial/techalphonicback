<?php

namespace App\Http\Controllers\Admin;

use App\Ai\AiException;
use App\Ai\AiManager;
use App\Ai\AiSettings;
use App\Ai\Pseo\PseoContentWriter;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\AiGeneration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/** Settings › AI content (provider, model, encrypted keys, budget) and the AI writing log. */
class AiController extends Controller
{
    private const MODEL_RULE = ['nullable', 'string', 'max:100', 'regex:/^[A-Za-z0-9._:\/-]+$/'];

    public function __construct(private AiManager $ai, private PseoContentWriter $writer) {}

    public function edit(Request $request)
    {
        abort_unless($request->user()->can('ai.configure'), 403);
        $settings = $this->ai->settings();

        return view('admin.ai.settings', [
            's' => $settings,
            'providers' => AiSettings::PROVIDERS,
            'masked' => collect(AiSettings::PROVIDERS)->keys()->mapWithKeys(fn ($p) => [$p => $settings->maskedKey($p)])->all(),
            'usage' => $this->usage($settings),
            'recent' => AiGeneration::with('subject:id,title')->latest()->limit(6)->get(),
            'queue' => config('queue.default'),
        ]);
    }

    public function update(Request $request)
    {
        abort_unless($request->user()->can('ai.configure'), 403);
        $providers = array_keys(AiSettings::PROVIDERS);
        $rules = [
            'ai_enabled' => ['nullable', 'boolean'],
            'ai_provider' => ['required', Rule::in($providers)],
            'ai_effort' => ['required', Rule::in(array_keys(AiSettings::EFFORTS))],
            'ai_temperature' => ['required', 'numeric', 'between:0,1.5'],
            'ai_max_tokens' => ['required', 'integer', 'between:2000,64000'],
            'ai_fields' => ['required', 'array', 'min:1'],
            'ai_fields.*' => [Rule::in(array_keys(AiSettings::FIELDS))],
            'ai_monthly_budget' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'ai_price_input' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'ai_price_output' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'ai_brand_voice' => ['nullable', 'string', 'max:1000'],
            'ai_instructions' => ['nullable', 'string', 'max:3000'],
        ];
        foreach ($providers as $p) {
            $rules["ai_{$p}_model"] = self::MODEL_RULE;
            $rules["ai_{$p}_key"] = ['nullable', 'string', 'min:10', 'max:400', 'regex:/^\S+$/'];
            $rules["ai_{$p}_key_remove"] = ['nullable', 'boolean'];
        }
        $data = $request->validate($rules, ['ai_fields.required' => 'Choose at least one part of the page for AI to write.']);

        $settings = $this->ai->settings();
        $keysChanged = [];
        DB::transaction(function () use ($data, $request, $providers, $settings, &$keysChanged) {
            $settings->set('ai_enabled', $request->boolean('ai_enabled') ? '1' : '0');
            foreach (['ai_provider', 'ai_effort', 'ai_temperature', 'ai_max_tokens', 'ai_monthly_budget', 'ai_price_input', 'ai_price_output', 'ai_brand_voice', 'ai_instructions'] as $k) {
                $settings->set($k, isset($data[$k]) ? (string) $data[$k] : null);
            }
            $settings->set('ai_fields', json_encode(array_values($data['ai_fields'])));
            foreach ($providers as $p) {
                $settings->set("ai_{$p}_model", $data["ai_{$p}_model"] ?? null);
                // A blank key field keeps the saved key; it is only replaced or removed explicitly.
                if (! empty($data["ai_{$p}_key"])) {
                    $settings->setApiKey($p, trim($data["ai_{$p}_key"]));
                    $keysChanged[] = $p.' key updated';
                } elseif ($request->boolean("ai_{$p}_key_remove")) {
                    $settings->setApiKey($p, null);
                    $keysChanged[] = $p.' key removed';
                }
            }
        });

        ActivityLog::record('ai_settings_updated', null, 'AI settings updated', [
            'provider' => $data['ai_provider'],
            'model' => $settings->model($data['ai_provider']),
            'keys' => $keysChanged ?: null,
        ]);

        $settings = $this->ai->settings();
        if ($settings->enabled() && ! $settings->hasKey()) {
            return back()->with('warning', 'Saved, but there is no API key for '.AiSettings::PROVIDERS[$settings->provider()]['label'].' yet, so AI writing is not available.');
        }

        return back()->with('success', 'AI settings saved.');
    }

    /** Free metadata call that proves the key + model work. Accepts an unsaved key typed into the form. */
    public function test(Request $request)
    {
        abort_unless($request->user()->can('ai.configure'), 403);
        $data = $request->validate([
            'provider' => ['required', Rule::in(array_keys(AiSettings::PROVIDERS))],
            'model' => self::MODEL_RULE,
            'key' => ['nullable', 'string', 'max:400'],
        ]);

        try {
            $name = $this->ai->driver($data['provider'], $data['model'] ?? null, $data['key'] ?? null)->test();

            return response()->json(['ok' => true, 'message' => 'Connected'.($name ? ' · '.$name : '').'.']);
        } catch (AiException $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()]);
        }
    }

    public function models(Request $request)
    {
        abort_unless($request->user()->can('ai.configure'), 403);
        $data = $request->validate([
            'provider' => ['required', Rule::in(array_keys(AiSettings::PROVIDERS))],
            'key' => ['nullable', 'string', 'max:400'],
        ]);

        try {
            return response()->json(['ok' => true, 'models' => $this->ai->driver($data['provider'], null, $data['key'] ?? null)->models()]);
        } catch (AiException $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()]);
        }
    }

    public function generations(Request $request)
    {
        abort_unless($request->user()->canAny(['ai.generate', 'ai.configure']), 403);
        $query = AiGeneration::with(['subject:id,title,slug,status', 'user:id,name'])->latest();
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }
        if ($request->query('similar')) {
            $query->where('similarity', '>=', 0.35);
        }
        if ($batch = $request->query('batch')) {
            $query->where('batch_id', $batch);
        }

        return view('admin.ai.generations', [
            'items' => $query->paginate(30)->withQueryString(),
            'usage' => $this->usage($this->ai->settings()),
            'counts' => AiGeneration::selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status'),
        ]);
    }

    private function usage(AiSettings $settings): array
    {
        $month = AiGeneration::thisMonth();

        return [
            'runs' => (clone $month)->count(),
            'succeeded' => (clone $month)->where('status', 'succeeded')->count(),
            'failed' => (clone $month)->where('status', 'failed')->count(),
            'pending' => AiGeneration::pending()->count(),
            'tokens' => (int) (clone $month)->sum(DB::raw('input_tokens + output_tokens')),
            'cost' => $this->writer->spentThisMonth(),
            'budget' => $settings->monthlyBudget(),
            'priced' => $settings->cost(1, 1) !== null,
        ];
    }
}
