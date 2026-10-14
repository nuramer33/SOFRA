@php($rtl = \App\Support\Locales::isRtl($language))
<!DOCTYPE html>
<html lang="{{ $language }}" dir="{{ $rtl ? 'rtl' : 'ltr' }}">
<body style="margin:0;padding:24px;background:#f4f2e7;font-family:Arial,Helvetica,sans-serif;color:#1f2417">
    <div style="max-width:520px;margin:0 auto;background:#ffffff;border-radius:20px;padding:28px;border:1px solid #d2d1b4">
        <p style="margin:0;color:#656b56;font-size:14px">{{ __('app.review.mail_intro', ['shop' => $shop->name]) }}</p>
        <p style="margin:12px 0 0;font-size:34px;letter-spacing:4px;color:#d8a93f">{{ str_repeat('★', $review->rating) }}<span style="color:#d2d1b4">{{ str_repeat('★', 5 - $review->rating) }}</span></p>

        @if ($review->comment)
            <p style="margin:16px 0 0;font-size:17px;line-height:1.7;white-space:pre-line">{{ $review->comment }}</p>
        @endif

        <table style="margin-top:20px;font-size:14px;color:#4d5340" cellpadding="4">
            <tr><td>{{ __('app.review.name') }}</td><td><strong>{{ $review->name ?: __('app.review.anonymous') }}</strong></td></tr>
            @if ($review->contact)
                <tr><td>{{ __('app.review.contact') }}</td><td><strong dir="ltr">{{ $review->contact }}</strong></td></tr>
            @endif
            <tr><td>{{ __('app.review.when') }}</td><td dir="ltr">{{ $review->created_at->format('Y-m-d H:i') }}</td></tr>
        </table>

        @if ($review->photo_path)
            <p style="margin:16px 0 0;font-size:14px;color:#656b56">{{ __('app.review.mail_photo') }}</p>
        @endif

        <p style="margin:24px 0 0"><a href="{{ route('reviews.index') }}" style="display:inline-block;background:#55642f;color:#f4f2e7;text-decoration:none;padding:12px 22px;border-radius:999px;font-size:15px">{{ __('app.review.mail_cta') }}</a></p>
    </div>
</body>
</html>