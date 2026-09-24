<?php

namespace Tests\Feature\Dashboard;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Vite;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ViteManifestCompatibilityTest extends TestCase
{
    use RefreshDatabase;

    private string $buildDirectory = 'build-dashboard-test';

    protected function setUp(): void
    {
        parent::setUp();

        File::ensureDirectoryExists(public_path($this->buildDirectory));
        File::put(public_path($this->buildDirectory.'/manifest.json'), json_encode([
            'resources/css/app.css' => [
                'file' => 'assets/app.css',
                'src' => 'resources/css/app.css',
                'isEntry' => true,
            ],
        ], JSON_THROW_ON_ERROR));
        app(Vite::class)->useBuildDirectory($this->buildDirectory);
    }

    protected function tearDown(): void
    {
        app(Vite::class)->useBuildDirectory('build');
        File::deleteDirectory(public_path($this->buildDirectory));

        parent::tearDown();
    }

    public function test_authenticated_dashboard_renders_with_the_established_application_stylesheet_entry(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get('/dashboard')->assertOk();
    }
}
