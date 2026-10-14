# Sofra

**English** · [العربية](README.ar.md) · [Türkçe](README.tr.md)

Sofra is a digital menu and WhatsApp ordering platform for small shops: cafés, restaurants, perfume and accessory stores, Instagram businesses.

A shop owner signs up, adds categories and products with photos and prices, and gets a public link and a QR code. Customers open the menu on their phone, build an order, and send it through WhatsApp. **Sofra never stores customer orders**: the order lives in the chat between the customer and the shop.

- Three languages: Arabic (right-to-left, default), Turkish and English. The menu follows the customer's phone language.
- Multi-tenant: every shop's data is isolated at the database query level.
- Subscription: 60 days free, then a monthly plan (paid manually or automatically with Stripe).

## Features

**For customers**
- Browse the menu, search, open product details, add to a cart that is saved in their own browser.
- Choose dine-in (with an optional table number), pickup or delivery.
- Send the order through a ready-made WhatsApp message. Prices are always taken from the database, never from the browser.
- "Goes well with" suggestions and offers. Nothing is added to the cart automatically.
- Private ratings for the owner.

**For shop owners**
- Categories, products, photos (compressed to WebP), prices, sale prices.
- **Today** page for quick changes from a phone: pause new orders, sold out today, edit price, name, description or photo, switch offers on or off.
- Ordering hours with a time zone, including shifts that cross midnight.
- Suggestions and discounts that the owner configures; discounts are calculated on the server.
- Privacy-friendly analytics: menu views, product views, add-to-cart, WhatsApp button clicks. Counters only, no personal data. A WhatsApp click is never presented as a confirmed order.
- One QR code per shop (brand color, print-ready PNG/SVG and an A6 table card). `?table=5` pre-fills the table number; no separate QR per table.
- Two menu looks: **Classic** (cafés, restaurants) and **Luxe** (perfume, accessories, gifts).
- Delivery fee and minimum order, address, opening hours, maps and Instagram links.

**For the platform administrator**
- Shop list with search and filters, CSV export.
- Record payments, give free days, suspend and re-activate, with a history of actions.
- Subscription states: trial, ending soon, active, past due, expired, manually suspended. What the public menu does after expiry is configurable.

## Tech stack

Laravel 13 · PHP 8.3 · MySQL · Blade · Tailwind CSS v4 · Alpine.js · Vite

## Quick start

Requirements: PHP 8.3 (with the GD or Imagick extension), Composer, Node.js, MySQL.

```bash
git clone <this repository> sofra
cd sofra
composer install
cp .env.example .env
php artisan key:generate
```

Create a MySQL database and set `DB_*` in `.env`, then:

```bash
php artisan migrate --seed
php artisan storage:link
npm install
npm run build
php artisan serve
```

Open http://localhost:8000.

Local demo accounts (created by the seeder, for development only):

| Role | Email | Password |
| --- | --- | --- |
| Shop owner | `demo@sofra.test` | `password` |
| Administrator | `admin@sofra.test` | `password` |

The public demo menu is at `/demo`. On a real server create the administrator with `php artisan sofra:admin you@example.com` and never use the demo accounts.

## Configuration

Product settings live in `config/sofra.php`; secrets and per-environment values go in `.env` (see `.env.example`).

| Variable | Purpose |
| --- | --- |
| `SOFRA_MONTHLY_PRICE` | Price text shown to owners, for example `$12` |
| `SOFRA_SUPPORT_WHATSAPP`, `SOFRA_SUPPORT_EMAIL` | Where owners contact you to renew |
| `SOFRA_EXPIRED_MENU` | Public menu after expiry: `visible`, `read_only` (default) or `suspended` |
| `STRIPE_SECRET`, `STRIPE_PRICE_ID`, `STRIPE_WEBHOOK_SECRET` | Automatic monthly payment with Stripe. Leave empty to keep payments manual |
| `MAIL_*` | SMTP for reviews and subscription reminders. Test with `php artisan sofra:mail-test you@example.com` |

### Automatic payment with Stripe (optional)

1. In Stripe, create a recurring monthly Price and copy its id (`price_...`).
2. Enable the Customer portal (card update and cancellation).
3. Add a webhook endpoint `https://your-domain/stripe/webhook` for: `checkout.session.completed`, `invoice.paid`, `invoice.payment_failed`, `customer.subscription.updated`, `customer.subscription.deleted`.
4. Put the keys in `.env`.

Owners enter their card on Stripe's own page; Sofra never sees or stores it. Paid time is granted only by the signature-verified webhook.

## Scheduled tasks

Add one cron line on the server:

```
* * * * * cd /path/to/sofra && php artisan schedule:run >> /dev/null 2>&1
```

It runs the daily backup (`sofra:backup`), subscription reminders (`sofra:subscription-reminders`) and analytics cleanup (`sofra:prune-analytics`).

## Tests

```bash
php artisan test --compact
vendor/bin/pint --format agent
```

## Privacy and security notes

- No customer orders, names, phone numbers or addresses are stored by the server. Analytics store daily counters only, with a retention period each shop chooses.
- Tenant isolation, server-side price and discount calculation, rate limits on public endpoints, validated uploads and a signed Stripe webhook.
- The privacy policy and terms pages are drafts and should be reviewed by a lawyer before launch.

## Contributing

Issues and pull requests are welcome. Please run the tests and Pint before opening a pull request.
