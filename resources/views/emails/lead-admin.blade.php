@extends('emails.layout')

@section('body')
    <h1 style="margin:0 0 8px;font-size:22px;color:#0B0D12;">New enquiry received</h1>
    <p style="margin:0 0 24px;color:#7C7C7C;">{{ $lead->created_at->format('d M Y, h:i A') }} · {{ $lead->form_name }}</p>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
        @foreach(['Name' => $lead->name, 'Phone' => $lead->phone, 'Email' => $lead->email, 'Company' => $lead->company, 'Service' => $lead->service, 'Location' => $lead->location, 'Budget' => $lead->budget, 'Website' => $lead->website, 'Source page' => $lead->source_page] as $label => $value)
            @if($value)
                <tr>
                    <td style="padding:10px 12px;border-bottom:1px solid #E7E9EE;width:130px;color:#7C7C7C;font-size:13px;">{{ $label }}</td>
                    <td style="padding:10px 12px;border-bottom:1px solid #E7E9EE;font-weight:bold;">{{ $value }}</td>
                </tr>
            @endif
        @endforeach
    </table>
    @if($lead->message)
        <p style="margin:24px 0 8px;color:#7C7C7C;font-size:13px;">Message</p>
        <div style="background:#F6F7F9;border-radius:12px;padding:16px;white-space:pre-line;">{{ $lead->message }}</div>
    @endif
    <p style="margin:28px 0 0;">
        <a href="{{ route('admin.leads.show', $lead) }}" style="display:inline-block;background:#FE5C36;color:#fff;text-decoration:none;padding:12px 22px;border-radius:10px;font-weight:bold;">Open in admin</a>
        <a href="tel:{{ preg_replace('/[^\d+]/', '', $lead->phone) }}" style="display:inline-block;margin-left:8px;color:#0B0D12;padding:12px 10px;">Call {{ $lead->phone }}</a>
    </p>
@endsection
