@php
    $rtl = \App\Support\Locales::isRtl($language);
    $date = $shop->paymentDueAt()?->locale($language)->translatedFormat('j F Y');
    $body = match ($stage) {
        'payment_failed' => __('billing.mail.payment_failed', ['shop' => $shop->name]),
        'expired' => __('billing.mail.expired', ['shop' => $shop->name]),
        'past_due' => __('billing.mail.past_due', ['shop' => $shop->name, 'date' => $date]),
        default => __('billing.mail.ending', ['shop' => $shop->name, 'date' => $date]),
    };
@endphp
<!DOCTYPE html>
<html lang="{{ $language }}" dir="{{ $rtl ? 'rtl' : 'ltr' }}">
<body style="margin:0;padding:24px;background:#f4f2e7;font-family:Arial,Helvetica,sans-serif;color:#1f2417">
    <div style="max-width:520px;margin:0 auto;background:#ffffff;border-radius:20px;padding:28px;border:1px solid #d2d1b4">
        <p style="margin:0;font-size:16px">{{ __('billing.mail.hello', ['name' => $owner->name]) }}</p>
        <p style="margin:14px 0 0;font-size:17px;line-height:1.7">{{ $body }}</p>
        <p style="margin:24px 0 0"><a href="{{ route('billing.show') }}" style="display:inline-block;background:#55642f;color:#f4f2e7;text-decoration:none;padding:12px 22px;border-radius:999px;font-size:15px">{{ __('billing.mail.cta') }}</a></p>
        <p style="margin:20px 0 0;font-size:13px;color:#656b56">{{ __('billing.mail.footer') }}</p>
    </div>
</body>
</html>
