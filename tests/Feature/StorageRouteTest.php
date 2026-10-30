<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StorageRouteTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_uploaded_files_are_served_without_the_storage_symlink(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('shops/1/logo/a.webp', 'img');

        $this->get('/storage/shops/1/logo/a.webp')->assertOk();
        $this->get('/storage/shops/1/logo/missing.webp')->assertNotFound();
    }
}
