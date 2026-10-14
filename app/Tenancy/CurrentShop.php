<?php

namespace App\Tenancy;

use App\Models\Shop;

/**
 * Holds "which shop is this request about?".
 *
 * - Dashboard requests: set from the logged-in user (SetTenantFromUser middleware).
 * - Public menu requests (Phase 3): set from the {slug} in the URL.
 *
 * It's registered as a *scoped* singleton, so it's reset for every request.
 */
class CurrentShop
{
    private ?Shop $shop = null;

    public function set(Shop $shop): void
    {
        $this->shop = $shop;
    }

    public function get(): ?Shop
    {
        return $this->shop;
    }

    public function id(): ?int
    {
        return $this->shop?->id;
    }

    public function check(): bool
    {
        return $this->shop !== null;
    }

    public function forget(): void
    {
        $this->shop = null;
    }
}
