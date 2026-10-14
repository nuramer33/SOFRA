<?php

namespace App\Support;

use App\Models\Product;
use App\Models\ProductSuggestion;
use App\Models\Shop;
use Illuminate\Support\Collection;

/**
 * Turns a cart into the ready-to-send WhatsApp message + wa.me link.
 *
 * The message is written in the SHOP's main language (the owner reads it),
 * with prices taken from the database — never from the customer's browser.
 */
class WhatsAppOrder
{
    /** No 0/O or 1/I/L, so the code is easy to read out loud. */
    private const REFERENCE_ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    /**
     * @param  list<array{product: Product, quantity: int, total: float}>  $lines
     * @param  array{type: string, table?: ?string, address?: ?string, name?: ?string, note?: ?string}  $details
     */
    private function __construct(
        public readonly Shop $shop,
        public readonly array $lines,
        public readonly array $details,
        public readonly string $reference,
    ) {}

    /**
     * @param  Collection<int, Product>  $products  keyed by id
     * @param  array<int, int>  $quantities  product id => quantity
     * @param  Collection<int, ProductSuggestion>|null  $suggestions  offers that may apply to this order
     */
    public static function make(Shop $shop, Collection $products, array $quantities, array $details, ?Collection $suggestions = null): self
    {
        $discounts = Offers::discounts($quantities, $products, $suggestions ?? collect());

        $lines = [];
        foreach ($quantities as $productId => $quantity) {
            $product = $products->get($productId);
            $discount = $discounts[$productId] ?? 0.0;
            $lines[] = [
                'product' => $product,
                'quantity' => $quantity,
                'discount' => $discount,
                'total' => round((float) $product->price * $quantity - $discount, 2),
            ];
        }

        return new self($shop, $lines, $details, self::newReference());
    }

    /** Total saved through offers (already taken off the line totals). */
    public function savings(): float
    {
        return round(array_sum(array_column($this->lines, 'discount')), 2);
    }

    public function subtotal(): float
    {
        return round(array_sum(array_column($this->lines, 'total')), 2);
    }

    public function deliveryFee(): float
    {
        return $this->shop->deliveryFeeFor($this->details['type']);
    }

    public function total(): float
    {
        return round($this->subtotal() + $this->deliveryFee(), 2);
    }

    public function message(): string
    {
        $locale = $this->shop->default_locale;
        $t = fn (string $key, array $replace = []) => __("app.order.{$key}", $replace, $locale);
        $money = fn (float|string $amount) => Money::format($amount, $this->shop->currency, $locale);

        // LRI…PDI (invisible "left-to-right isolate") keeps "#K7Q2" from
        // turning into "K7Q2#" inside Arabic text.
        $text = ["🧾 *{$t('title')}* · \u{2066}#{$this->reference}\u{2069}", ''];

        foreach ($this->lines as $line) {
            $name = $line['product']->getTranslation('name', $locale);
            $text[] = "{$line['quantity']} × {$name}  —  {$money($line['total'])}";
        }

        $text[] = '';
        if ($this->savings() > 0) {
            $text[] = "\u{1F3F7} ".__('suggestions.order.discount', [], $locale).": {$money($this->savings())}";
        }
        if ($this->deliveryFee() > 0) {
            $text[] = "\u{1F6F5} ".__('shopinfo.order.delivery_fee', [], $locale).": {$money($this->deliveryFee())}";
        }
        $text[] = "*{$t('total')}: {$money($this->total())}*";
        $text[] = '';

        $type = $t('types.'.$this->details['type']);
        if (filled($this->details['table'] ?? null)) {
            $type .= ' — '.$t('table', ['table' => self::oneLine($this->details['table'])]);
        }
        $text[] = "🍽 {$t('type')}: {$type}";

        foreach (['name' => '👤', 'address' => '📍', 'note' => '📝'] as $field => $icon) {
            if (filled($this->details[$field] ?? null)) {
                $text[] = "{$icon} {$t($field)}: ".self::oneLine($this->details[$field]);
            }
        }

        $text[] = '';
        $text[] = '— '.$t('footer', ['shop' => $this->shop->getTranslation('name', $locale)]);

        return implode("\n", $text);
    }

    public function url(): string
    {
        return 'https://wa.me/'.$this->shop->whatsapp_number.'?text='.rawurlencode($this->message());
    }

    private static function newReference(): string
    {
        $alphabet = self::REFERENCE_ALPHABET;
        $code = '';
        for ($i = 0; $i < 4; $i++) {
            $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }

        return $code;
    }

    /**
     * Customer text goes into a chat message: keep it on one line and
     * strip WhatsApp formatting characters so it can't fake "bold" sections.
     */
    private static function oneLine(string $value): string
    {
        return trim(preg_replace('/\s+/u', ' ', str_replace(['*', '_', '~', '`'], '', $value)));
    }
}
