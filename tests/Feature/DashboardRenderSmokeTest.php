<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DashboardRenderSmokeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public static function emailProvider(): array
    {
        return [
            'national' => ['admin@tchadedu.com'],
            'regional' => ['occidental@tchadedu.com'],
            'departmental' => ['ndjamena@tchadedu.com'],
            'establishment' => ['eboue@tchadedu.com'],
        ];
    }

    #[DataProvider('emailProvider')]
    public function test_dashboard_renders_without_error(string $email): void
    {
        $user = User::where('email', $email)->firstOrFail();

        $response = $this->actingAs($user)->get('/admin');
        $response->assertSuccessful();
    }

    #[DataProvider('emailProvider')]
    public function test_reports_page_renders_without_error_when_accessible(string $email): void
    {
        $user = User::where('email', $email)->firstOrFail();

        $response = $this->actingAs($user)->get('/admin/reports');

        if ($user->scopeLevel() === 'self') {
            $response->assertForbidden();
        } else {
            $response->assertSuccessful();
        }
    }
}
