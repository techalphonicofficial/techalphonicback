<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>{{ setting('site_name') }}</title></head>
<body style="margin:0;padding:0;background:#F6F7F9;font-family:Arial,Helvetica,sans-serif;color:#1E2330;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#F6F7F9;padding:32px 12px;">
    <tr><td align="center">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;background:#ffffff;border-radius:16px;overflow:hidden;border:1px solid #E7E9EE;">
            <tr><td style="background:#0B0D12;padding:24px 32px;">
                <table role="presentation" cellpadding="0" cellspacing="0"><tr>
                    <td style="padding-right:10px;"><table role="presentation" cellpadding="0" cellspacing="3"><tr><td style="width:10px;height:10px;background:#53AFBE;border-radius:2px"></td><td style="width:10px;height:10px;background:#FE5C36;border-radius:2px"></td></tr><tr><td style="width:10px;height:10px;background:#FEB449;border-radius:2px"></td><td style="width:10px;height:10px;background:#7C7C7C;border-radius:2px"></td></tr></table></td>
                    <td style="color:#ffffff;font-size:18px;font-weight:bold;">{{ setting('site_name') }}</td>
                </tr></table>
            </td></tr>
            <tr><td style="padding:32px;font-size:15px;line-height:1.6;">@yield('body')</td></tr>
            <tr><td style="padding:20px 32px;background:#F6F7F9;font-size:12px;color:#7C7C7C;">
                {{ setting('site_name') }} · {{ app(\App\Services\SettingsService::class)->fullAddress() }}<br>
                {{ setting('phone') }} · {{ setting('email') }}
            </td></tr>
        </table>
    </td></tr>
</table>
</body>
</html>
