<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Application;
use App\Models\User;
use App\Services\ApplicationWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationBellSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_with_pending_database_notifications_renders_without_error(): void
    {
        $this->seed();

        $etab = User::where('email', 'eboue@tchadedu.com')->firstOrFail();
        $dept = User::where('email', 'ndjamena@tchadedu.com')->firstOrFail();
        $app = Application::where('status', Application::STATUS_SOUMISE)->firstOrFail();

        app(ApplicationWorkflowService::class)->approve($app, $dept);

        $this->assertGreaterThan(0, $etab->fresh()->notifications()->count());

        $response = $this->actingAs($etab)->get('/admin');
        $response->assertSuccessful();
    }
}
