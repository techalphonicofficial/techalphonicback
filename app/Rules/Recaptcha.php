<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;

class Recaptcha implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        try {
            $res = Http::asForm()->timeout(5)->post('https://www.google.com/recaptcha/api/siteverify', [
                'secret' => config('services.recaptcha.secret'),
                'response' => $value,
                'remoteip' => request()->ip(),
            ])->json();
        } catch (\Throwable) {
            return; // fail open on Google outage; honeypot + rate limit still apply
        }

        if (! ($res['success'] ?? false) || ($res['score'] ?? 1) < (float) config('services.recaptcha.min_score', 0.5)) {
            $fail('Spam check failed. Please try again or contact us by phone.');
        }
    }
}
