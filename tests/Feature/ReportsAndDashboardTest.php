<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Widgets\ApplicationStatsOverview;
use App\Filament\Widgets\ApplicationsByExamTypeChart;
use App\Filament\Widgets\EstablishmentsOverview;
use App\Filament\Widgets\GenderDistributionChart;
use App\Filament\Widgets\RecentRejectionsTable;
use App\Filament\Widgets\SubmissionsTrendChart;
use App\Models\Application;
use App\Models\Student;
use App\Models\User;
use App\Services\ApplicationStatisticsService;
use App\Services\ApplicationWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Ces tests tournent sur la vraie base seedée (RefreshDatabase + seeders),
 * pour vérifier le module Rapports & Tableaux de bord dans des conditions
 * proches du réel plutôt qu'avec des fixtures minimales.
 */
class ReportsAndDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_national_user_sees_the_full_dashboard_widget_set(): void
    {
        $national = User::where('email', 'admin@tchadedu.com')->firstOrFail();

        $this->actingAs($national);

        $this->assertTrue($this->canViewAs($national, ApplicationStatsOverview::class));
        $this->assertTrue($this->canViewAs($national, EstablishmentsOverview::class));
        $this->assertTrue($this->canViewAs($national, ApplicationsByExamTypeChart::class));
        $this->assertTrue($this->canViewAs($national, SubmissionsTrendChart::class));
        $this->assertTrue($this->canViewAs($national, GenderDistributionChart::class));
        $this->assertTrue($this->canViewAs($national, RecentRejectionsTable::class));
    }

    public function test_establishment_user_only_sees_the_widgets_relevant_to_them(): void
    {
        $etablissement = User::where('email', 'eboue@tchadedu.com')->firstOrFail();

        $this->assertTrue($this->canViewAs($etablissement, ApplicationStatsOverview::class));
        $this->assertFalse($this->canViewAs($etablissement, EstablishmentsOverview::class));
        $this->assertFalse($this->canViewAs($etablissement, GenderDistributionChart::class));
        $this->assertFalse($this->canViewAs($etablissement, RecentRejectionsTable::class));
    }

    public function test_reports_page_loads_for_departmental_user_and_export_streams_a_csv(): void
    {
        $dept = User::where('email', 'ndjamena@tchadedu.com')->firstOrFail();

        $response = $this->actingAs($dept)->get('/admin/reports');
        $response->assertSuccessful();
        $response->assertSee('Rapports');

        Livewire::actingAs($dept)
            ->test(\App\Filament\Pages\Reports::class)
            ->assertSuccessful()
            ->callAction('export')
            ->assertFileDownloaded();
    }

    public function test_reports_page_is_not_accessible_to_a_candidate(): void
    {
        $candidat = User::factory()->create(['student_id' => 1]);

        $this->actingAs($candidat)->get('/admin/reports')->assertForbidden();
    }

    public function test_breakdown_respects_perimeter_for_a_departmental_user(): void
    {
        $dept = User::where('email', 'ndjamena@tchadedu.com')->firstOrFail();
        $this->actingAs($dept);

        $rows = app(ApplicationStatisticsService::class)->breakdown();
        $total = collect($rows)->sum('total');

        $expected = Application::query()
            ->whereHas('establishment', fn ($q) => $q->where('department_id', $dept->department_id))
            ->count();

        $this->assertSame($expected, $total);
    }

    public function test_status_counts_add_up_to_total_after_a_workflow_transition(): void
    {
        $etab = User::where('email', 'eboue@tchadedu.com')->firstOrFail();
        $dept = User::where('email', 'ndjamena@tchadedu.com')->firstOrFail();

        $existing = Application::where('establishment_id', $etab->establishment_id)->firstOrFail();

        $nouvelEleve = Student::create([
            'establishment_id' => $etab->establishment_id,
            'last_name' => 'Test',
            'first_name' => 'Nouveau',
            'birth_date' => '2009-05-05',
            'sex' => 'M',
        ]);

        $app = Application::create([
            'student_id' => $nouvelEleve->id,
            'establishment_id' => $etab->establishment_id,
            'exam_session_id' => $existing->exam_session_id,
            'status' => Application::STATUS_BROUILLON,
        ]);

        $this->actingAs($etab);
        app(ApplicationWorkflowService::class)->submit($app, $etab);

        $this->actingAs($dept);
        $counts = app(ApplicationStatisticsService::class)->statusCounts();

        $sum = $counts['brouillon'] + $counts['en_cours'] + $counts['a_corriger']
            + $counts['inscrit'] + $counts['rejetee'] + $counts['annulee'];

        $this->assertSame($counts['total'], $sum);
    }

    private function canViewAs(User $user, string $widgetClass): bool
    {
        $this->actingAs($user);

        return $widgetClass::canView();
    }
}
