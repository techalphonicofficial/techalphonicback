<?php

namespace App\Listeners;

use App\Events\LeadSubmitted;
use App\Mail\LeadConfirmationMail;
use App\Mail\NewLeadAdminMail;
use App\Services\SettingsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendLeadNotifications implements ShouldQueue
{
    public int $tries = 3;

    public array $backoff = [30, 120, 600];

    public function __construct(private SettingsService $settings) {}

    public function handle(LeadSubmitted $event): void
    {
        $lead = $event->lead;
        $to = $this->settings->get('lead_notify_email') ?: $this->settings->get('email');

        if ($to) {
            Mail::to($to)->send(new NewLeadAdminMail($lead));
        }

        if ($lead->email && $this->settings->bool('lead_autoreply_enabled')) {
            Mail::to($lead->email, $lead->name)->send(new LeadConfirmationMail($lead));
        }

        Log::channel('leads')->info('Lead notifications sent', ['lead_id' => $lead->id]);
    }
}
