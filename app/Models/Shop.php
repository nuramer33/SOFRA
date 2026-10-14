<?php

namespace App\Models;

use App\Support\Color;
use App\Support\ImageStore;
use App\Support\Locales;
use App\Support\Money;
use Database\Factories\ShopFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Spatie\Translatable\HasTranslations;

#[Fillable([
    'name', 'tagline', 'slug', 'logo_path', 'brand_color', 'whatsapp_number',
    'currency', 'default_locale', 'enabled_locales', 'template', 'is_active', 'order_types',
    'maps_url', 'instagram', 'delivery_fee', 'min_order', 'analytics_retention_days',
    'orders_paused', 'order_hours', 'timezone',
])]
class Shop extends Model
{
    /** @use HasFactory<ShopFactory> */
    use HasFactory, HasTranslations;

    /** How a customer can receive an order. */
    public const ORDER_TYPES = ['dine_in', 'pickup', 'delivery'];

    /** Fields stored as {"ar": "...", "tr": "...", "en": "..."} */
    public array $translatable = ['name', 'tagline', 'address', 'hours'];

    protected function casts(): array
    {
        return [
            'enabled_locales' => 'array',
            'order_types' => 'array',
            'orders_paused' => 'boolean',
            'order_hours' => 'array',
            'is_active' => 'boolean',
            'delivery_fee' => 'decimal:2',
            'min_order' => 'decimal:2',
            'qr_downloaded_at' => 'datetime',
            'trial_ends_at' => 'datetime',
            'paid_until' => 'datetime',
            'auto_renew' => 'boolean',
            'payment_failed_at' => 'datetime',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Subscription (free trial, then manual monthly payments)
    |--------------------------------------------------------------------------
    | trial_ends_at and paid_until are NOT fillable: only registration and
    | the admin panel set them, never a form an owner can submit.
    */

    public const BILLING_TRIAL = 'trial';

    public const BILLING_TRIAL_ENDING = 'trial_ending';

    /** Past the due date but inside the grace period: everything still works. */
    public const BILLING_PAST_DUE = 'past_due';

    /** Grace period over. What the public menu does is `sofra.billing.expired_menu`. */
    public const BILLING_EXPIRED = 'expired';

    /** Filter value only (admin panel): past due or expired. Never returned by billingStatus(). */
    public const BILLING_DUE = 'due';

    public const BILLING_PAID = 'paid';

    public const EXPIRED_MENU_MODES = ['visible', 'read_only', 'suspended'];

    public function startTrial(): void
    {
        $this->trial_ends_at = now()->addDays(config('sofra.billing.trial_days'));
    }

    public function billingStatus(): string
    {
        if ($this->paid_until?->isFuture()) {
            return self::BILLING_PAID;
        }

        if ($this->trial_ends_at?->isFuture()) {
            return $this->trialDaysLeft() <= config('sofra.billing.ending_soon_days')
                ? self::BILLING_TRIAL_ENDING
                : self::BILLING_TRIAL;
        }

        $dueAt = $this->paymentDueAt();

        return $dueAt !== null && $dueAt->copy()->addDays(config('sofra.billing.grace_days'))->isFuture()
            ? self::BILLING_PAST_DUE
            : self::BILLING_EXPIRED;
    }

    /**
     * Server-side truth: has the grace period run out? (The public demo shop never expires.)
     */
    public function subscriptionExpired(): bool
    {
        return $this->slug !== config('sofra.demo_slug') && $this->billingStatus() === self::BILLING_EXPIRED;
    }

    /** What the public menu does for an expired shop: visible | read_only | suspended. */
    public static function expiredMenuMode(): string
    {
        $mode = config('sofra.billing.expired_menu');

        return in_array($mode, self::EXPIRED_MENU_MODES, true) ? $mode : 'read_only';
    }

    /**
     * Can customers open this menu? False when an administrator suspended the
     * shop, or when it is expired and the platform hides expired menus.
     */
    public function isPubliclyVisible(): bool
    {
        return $this->is_active && ! ($this->subscriptionExpired() && self::expiredMenuMode() === 'suspended');
    }

    /** Menu stays visible but ordering is off because the subscription expired. */
    public function ordersPausedBySubscription(): bool
    {
        return $this->subscriptionExpired() && self::expiredMenuMode() === 'read_only';
    }

    /** Days until the next payment is due (negative once it is overdue). */
    public function daysUntilDue(): ?int
    {
        $dueAt = $this->paymentDueAt();

        return $dueAt === null ? null : (int) ceil(now()->diffInHours($dueAt, false) / 24);
    }

    /**
     * Gives free days without a payment: extends the paid period if there is
     * one, otherwise the free period (counting from today when it has ended).
     */
    public function extendBy(int $days): void
    {
        if ($this->paid_until?->isFuture()) {
            $this->forceFill(['paid_until' => $this->paid_until->copy()->addDays($days)])->save();

            return;
        }

        $from = collect([now(), $this->trial_ends_at])->filter()->max();

        $this->forceFill(['trial_ends_at' => $from->copy()->addDays($days)])->save();
    }

    /** Whole days left in the free period (0 once it has ended). */
    public function trialDaysLeft(): int
    {
        if ($this->trial_ends_at === null || $this->trial_ends_at->isPast()) {
            return 0;
        }

        return (int) ceil(now()->diffInHours($this->trial_ends_at) / 24);
    }

    /** The date the shop has to start paying (end of trial or of last payment). */
    public function paymentDueAt(): ?Carbon
    {
        return collect([$this->trial_ends_at, $this->paid_until])->filter()->max();
    }

    /**
     * Record a payment received outside the app: extends the paid period by
     * $months, counting from whichever is later — now, the end of the trial,
     * or the end of the last paid period (so no free days are lost).
     */
    public function recordPayment(int $months): void
    {
        $from = collect([now(), $this->trial_ends_at, $this->paid_until])->filter()->max();

        $this->forceFill(['paid_until' => $from->copy()->addMonths($months)])->save();
    }

    /**
     * A payment confirmed by the payment provider: the paid period now runs to
     * the end of the period Stripe charged for (never shortened below what is
     * already paid), and any "payment failed" flag is cleared.
     */
    public function recordOnlinePayment(Carbon $periodEnd): void
    {
        $this->forceFill([
            'paid_until' => collect([$this->paid_until, $periodEnd])->filter()->max(),
            'payment_failed_at' => null,
            'last_billing_notice' => null, // allow reminders for the new period
        ])->save();
    }

    /** Renews by itself through a card on file at the payment provider. */
    public function hasAutoRenew(): bool
    {
        return $this->auto_renew && filled($this->stripe_subscription_id);
    }

    /**
     * Shops in a given billing state (for admin filters).
     */
    public function scopeBilling(Builder $query, string $status): Builder
    {
        $notPaid = fn (Builder $q) => $q->whereNull('paid_until')->orWhere('paid_until', '<=', now());
        $trialOver = fn (Builder $q) => $q->whereNull('trial_ends_at')->orWhere('trial_ends_at', '<=', now());
        $insideGrace = function (Builder $q) {
            $cutoff = now()->subDays(config('sofra.billing.grace_days'));
            $q->where('trial_ends_at', '>', $cutoff)->orWhere('paid_until', '>', $cutoff);
        };

        return match ($status) {
            self::BILLING_PAID => $query->where('paid_until', '>', now()),
            self::BILLING_TRIAL => $query->where($notPaid)->where('trial_ends_at', '>', now()),
            self::BILLING_DUE => $query->where($notPaid)->where($trialOver),
            self::BILLING_PAST_DUE => $query->where($notPaid)->where($trialOver)->where($insideGrace),
            self::BILLING_EXPIRED => $query->where($notPaid)->where($trialOver)->where(function (Builder $q) {
                $cutoff = now()->subDays(config('sofra.billing.grace_days'));
                $q->where(fn (Builder $inner) => $inner->whereNull('trial_ends_at')->orWhere('trial_ends_at', '<=', $cutoff))
                    ->where(fn (Builder $inner) => $inner->whereNull('paid_until')->orWhere('paid_until', '<=', $cutoff));
            }),
            default => $query,
        };
    }

    /**
     * Can this shop receive orders at all? Needs somewhere to send them to,
     * and a subscription that has not expired under the "read only" policy.
     */
    public function canReceiveOrders(): bool
    {
        return filled($this->whatsapp_number) && ! $this->ordersPausedBySubscription();
    }

    /**
     * Is the order button on the menu live right now? Also false while the
     * owner has paused orders or the shop is outside its ordering hours.
     */
    public function acceptsOrders(): bool
    {
        return $this->canReceiveOrders() && $this->orderingClosedReason() === null;
    }

    /**
     * Why ordering is closed right now: 'paused' (the owner's switch),
     * 'hours' (outside the weekly schedule) or null when it is open.
     */
    public function orderingClosedReason(?Carbon $at = null): ?string
    {
        if ($this->orders_paused) {
            return 'paused';
        }

        return $this->isWithinOrderHours($at) ? null : 'hours';
    }

    /**
     * Inside the weekly ordering hours? No schedule means always open.
     * A window like 22:00–02:00 runs past midnight into the next day.
     */
    public function isWithinOrderHours(?Carbon $at = null): bool
    {
        $hours = $this->order_hours;
        if (empty($hours)) {
            return true;
        }

        $local = ($at ?? now())->copy()->setTimezone($this->timezone ?: config('app.timezone'));
        $time = $local->format('H:i');
        $today = $hours[(string) $local->dayOfWeek] ?? null;
        $yesterday = $hours[(string) $local->copy()->subDay()->dayOfWeek] ?? null;

        if (($today['open'] ?? false) && self::timeInWindow($time, $today['from'], $today['to'])) {
            return true;
        }

        // Yesterday's window that crossed midnight is still running.
        return ($yesterday['open'] ?? false)
            && $yesterday['to'] <= $yesterday['from']
            && $time < $yesterday['to'];
    }

    /**
     * When ordering next opens (in the shop's time zone), or null without a schedule.
     */
    public function nextOrderingOpening(?Carbon $at = null): ?Carbon
    {
        $hours = $this->order_hours;
        if (empty($hours)) {
            return null;
        }

        $now = ($at ?? now())->copy()->setTimezone($this->timezone ?: config('app.timezone'));

        for ($offset = 0; $offset <= 7; $offset++) {
            $day = $now->copy()->addDays($offset)->startOfDay();
            $window = $hours[(string) $day->dayOfWeek] ?? null;
            if (! ($window['open'] ?? false)) {
                continue;
            }

            [$hour, $minute] = array_map('intval', explode(':', $window['from']));
            $opensAt = $day->copy()->setTime($hour, $minute);
            if ($opensAt->greaterThan($now)) {
                return $opensAt;
            }
        }

        return null;
    }

    private static function timeInWindow(string $time, string $from, string $to): bool
    {
        // from == to is rejected on input; treat it as "closed" if it ever appears.
        return $to > $from ? ($time >= $from && $time < $to) : ($from !== $to && $time >= $from);
    }

    /**
     * Order types this shop accepts, in a fixed order.
     *
     * @return list<string>
     */
    /**
     * The delivery charge added to an order of this type (only deliveries pay it).
     */
    public function deliveryFeeFor(string $orderType): float
    {
        return $orderType === 'delivery' ? (float) $this->delivery_fee : 0.0;
    }

    public function orderTypes(): array
    {
        return array_values(array_intersect(self::ORDER_TYPES, $this->order_types ?: self::ORDER_TYPES));
    }

    public function menuUrl(): string
    {
        return route('menu.show', $this);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * The person who registered the shop (its first user).
     */
    public function owner(): HasOne
    {
        return $this->hasOne(User::class)->oldestOfMany();
    }

    public function subscriptionEvents(): HasMany
    {
        return $this->hasMany(SubscriptionEvent::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class)->orderBy('sort_order');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class)->orderBy('sort_order');
    }

    /**
     * Languages this shop writes its menu in, default language first.
     *
     * @return list<string>
     */
    public function contentLocales(): array
    {
        $enabled = $this->enabled_locales ?: Locales::codes();
        $ordered = array_values(array_intersect(Locales::codes(), $enabled));

        return array_values(array_unique([$this->default_locale, ...$ordered]));
    }

    public function formatPrice(float|int|string $amount): string
    {
        return Money::format($amount, $this->currency);
    }

    public function logoUrl(string $variant = 'lg'): ?string
    {
        return ImageStore::url($this->logo_path, $variant);
    }

    /**
     * Text color that stays readable on top of the brand color
     * (white on dark brands, espresso on light ones like yellow).
     */
    public function brandForeground(): string
    {
        return Color::foregroundFor($this->brand_color);
    }

    /**
     * The brand color as text on the cream menu background (prices, links).
     * Light brands (yellow, pastel) get darkened just enough to stay readable.
     */
    public function brandOnPaper(): string
    {
        return Color::readableOn($this->brand_color);
    }

    /**
     * Validation rules for the public link (/{slug}). Shared by registration
     * and shop settings.
     */
    public static function slugRules(?self $ignore = null): array
    {
        return [
            'required', 'string', 'min:3', 'max:40',
            'regex:/^[a-z0-9](?:[a-z0-9-]*[a-z0-9])?$/', // lowercase, digits, inner hyphens
            Rule::notIn(config('sofra.reserved_slugs')),
            Rule::unique('shops', 'slug')->ignore($ignore),
        ];
    }
}
