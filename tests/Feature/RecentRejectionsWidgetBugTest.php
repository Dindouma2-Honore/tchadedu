<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Widgets\RecentRejectionsTable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RecentRejectionsWidgetBugTest extends TestCase
{
    use RefreshDatabase;

    public function test_widget_does_not_crash_when_there_are_zero_rejections(): void
    {
        $this->seed();
        $national = User::where('email', 'admin@tchadedu.com')->firstOrFail();

        // Confirme qu'on est bien dans le scénario qui plantait : 0 résultat.
        $this->assertSame(0, \App\Models\Application::whereIn('status', [
            'rejetee', 'a_corriger_departement', 'a_corriger_region', 'a_corriger_national',
        ])->count());

        Livewire::actingAs($national)->test(RecentRejectionsTable::class)->assertSuccessful();
    }

    public function test_full_dashboard_page_renders_with_zero_rejections(): void
    {
        $this->seed();
        $national = User::where('email', 'admin@tchadedu.com')->firstOrFail();

        $this->actingAs($national)->get('/admin')->assertSuccessful();
    }
}
