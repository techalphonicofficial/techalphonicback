<?php

namespace App\Services;

use App\Enums\LeadStatus;
use App\Events\LeadSubmitted;
use App\Models\Lead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LeadService
{
    public function capture(array $data, Request $request): Lead
    {
        $lead = Lead::create([
            ...collect($data)->only(['name', 'phone', 'email', 'company', 'service', 'location', 'budget', 'website', 'message'])->all(),
            'form_name' => $data['form_name'] ?? 'enquiry',
            'source_page' => substr((string) ($data['source_page'] ?? $request->headers->get('referer')), 0, 500),
            // First-touch UTMs are captured by the Next.js frontend and posted with the form.
            'utm' => collect($request->only(['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'gclid']))
                ->filter()->all() ?: null,
            'ip' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 500),
            'status' => LeadStatus::New,
        ]);

        // No PII in logs — id and source only.
        Log::channel('leads')->info('Lead captured', ['lead_id' => $lead->id, 'source' => $lead->source_page]);

        LeadSubmitted::dispatch($lead);

        return $lead;
    }
}
