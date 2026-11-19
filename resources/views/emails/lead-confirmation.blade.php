@extends('emails.layout')

@section('body')
    <h1 style="margin:0 0 16px;font-size:22px;color:#0B0D12;">Thanks, {{ \Illuminate\Support\Str::before($lead->name, ' ') }}!</h1>
    <p>We've received your enquiry{{ $lead->service ? ' about '.$lead->service : '' }} and a specialist from our team will get in touch within one business day.</p>
    <p>If it's urgent, you can reach us directly:</p>
    <ul style="padding-left:18px;">
        @if(setting('phone'))<li>Phone: <a href="{{ app(\App\Services\SettingsService::class)->telHref(setting('phone')) }}" style="color:#FE5C36;">{{ setting('phone') }}</a></li>@endif
        @if($wa = app(\App\Services\SettingsService::class)->whatsappUrl())<li>WhatsApp: <a href="{{ $wa }}" style="color:#FE5C36;">Chat with us</a></li>@endif
        @if(setting('email'))<li>Email: <a href="mailto:{{ setting('email') }}" style="color:#FE5C36;">{{ setting('email') }}</a></li>@endif
    </ul>
    <p style="margin-top:24px;">Warm regards,<br><strong>Team {{ setting('site_name') }}</strong></p>
    <p style="font-size:12px;color:#7C7C7C;margin-top:24px;">You received this email because you submitted a form on {{ parse_url(config('app.url'), PHP_URL_HOST) }}. If this wasn't you, please ignore it.</p>
@endsection
