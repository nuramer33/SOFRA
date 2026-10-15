<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class InstallableAppTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_dashboard_links_the_manifest_and_registers_the_service_worker(): void
    {
        $this->signInOwner();

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('rel="manifest" href="/manifest.webmanifest"', false)
            ->assertSee("register('/sw.js')", false);
    }

    public function test_manifest_points_at_files_that_exist(): void
    {
        $manifest = json_decode(file_get_contents(public_path('manifest.webmanifest')), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame('standalone', $manifest['display']);
        $this->assertFileExists(public_path('sw.js'));

        foreach ($manifest['icons'] as $icon) {
            $this->assertFileExists(public_path($icon['src']));
        }
    }
}
