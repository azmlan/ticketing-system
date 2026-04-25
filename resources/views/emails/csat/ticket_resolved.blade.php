<!DOCTYPE html>
<html dir="{{ app()->isLocale('ar') ? 'rtl' : 'ltr' }}" lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="font-family: sans-serif; padding: 24px; color: #1a1a1a; background: #f9f9f9;">
    <div style="max-width: 600px; margin: 0 auto; background: #fff; border-radius: 8px; padding: 32px;">
        <p style="font-size: 16px;">{{ __('csat.email.greeting', ['name' => $recipientName]) }}</p>
        <p style="font-size: 15px; line-height: 1.6;">
            {{ __('csat.email.body', [
                'display_number' => $displayNumber,
                'subject'        => $ticketSubject,
                'tech'           => $techName,
            ]) }}
        </p>
        <p style="margin-top: 24px;">
            <a href="{{ $ticketUrl }}" style="background: #4f46e5; color: #fff; padding: 10px 20px; border-radius: 6px; text-decoration: none; font-size: 14px;">
                {{ __('notifications.view_ticket', ['display_number' => $displayNumber]) }}
            </a>
        </p>
    </div>
</body>
</html>
