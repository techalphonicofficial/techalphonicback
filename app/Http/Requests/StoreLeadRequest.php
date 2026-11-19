<?php

namespace App\Http\Requests;

use App\Rules\Recaptcha;
use App\Services\SettingsService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Crypt;

class StoreLeadRequest extends FormRequest
{
    /** Minimum seconds between page render and submit — bots submit instantly. */
    private const MIN_FILL_SECONDS = 3;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $clean = fn ($v) => is_string($v) ? trim(strip_tags($v)) : $v;
        $this->merge([
            'name' => $clean($this->name),
            'company' => $clean($this->company),
            'message' => $clean($this->message),
            'phone' => preg_replace('/[^\d+\-\s()]/', '', (string) $this->phone),
            'email' => $this->email ? strtolower(trim($this->email)) : null,
            'website' => $this->website ? trim($this->website) : null,
        ]);
    }

    public function rules(): array
    {
        $rules = [
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'phone' => ['required', 'string', 'min:8', 'max:20', 'regex:/^[+\d][\d\s\-()]{6,}$/'],
            'email' => ['nullable', 'email:rfc', 'max:150'],
            'company' => ['nullable', 'string', 'max:150'],
            'service' => ['nullable', 'string', 'max:150'],
            'location' => ['nullable', 'string', 'max:100'],
            'budget' => ['nullable', 'string', 'max:50'],
            'website' => ['nullable', 'url:http,https', 'max:255'],
            'message' => ['nullable', 'string', 'max:3000'],
            'source_page' => ['nullable', 'string', 'max:500'],
            'form_name' => ['nullable', 'string', 'max:50'],
            // Honeypot: hidden field real users never fill.
            'fax_number' => ['prohibited'],
            '_ts' => [$this->is('api/*') ? 'nullable' : 'required', 'string'],
        ];

        if (app(SettingsService::class)->bool('recaptcha_enabled') && config('services.recaptcha.secret')) {
            $rules['g-recaptcha-response'] = ['required', new Recaptcha];
        }

        return $rules;
    }

    public function after(): array
    {
        return [function (Validator $v) {
            if ($this->is('api/*')) {
                return; // API clients are rate limited + honeypot-checked; no rendered form to time.
            }
            try {
                $renderedAt = (int) Crypt::decryptString((string) $this->input('_ts'));
            } catch (\Throwable) {
                $renderedAt = 0;
            }
            if ($renderedAt === 0 || time() - $renderedAt < self::MIN_FILL_SECONDS || time() - $renderedAt > 86400) {
                $v->errors()->add('form', 'Please refresh the page and try again.');
            }
        }];
    }

    public function messages(): array
    {
        return [
            'fax_number.prohibited' => 'Submission rejected.',
            'phone.regex' => 'Please enter a valid phone number.',
        ];
    }
}
