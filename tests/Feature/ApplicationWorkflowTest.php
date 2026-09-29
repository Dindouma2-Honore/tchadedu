<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Exceptions\InvalidApplicationTransitionException;
use App\Models\AcademicLevel;
use App\Models\AcademicYear;
use App\Models\Application;
use App\Models\Classe;
use App\Models\Department;
use App\Models\Establishment;
use App\Models\ExamSession;
use App\Models\ExamType;
use App\Models\Region;
use App\Models\Student;
use App\Models\User;
use App\Services\ApplicationWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private ApplicationWorkflowService $service;

    private User $etablissementUser;

    private User $departementUser;

    private User $regionUser;

    private User $nationalUser;

    private Application $application;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(ApplicationWorkflowService::class);

        $region = Region::create(['name' => 'Logone Occidental', 'code' => 'LO']);
        $autreRegion = Region::create(['name' => 'Chari-Baguirmi', 'code' => 'CB']);
        $department = Department::create(['region_id' => $region->id, 'name' => 'Moundou', 'code' => 'LO-01']);
        $establishment = Establishment::create([
            'region_id' => $region->id,
            'department_id' => $department->id,
            'code' => 'MOU-LYC-001',
            'name' => 'Lycée de Moundou',
            'type' => 'public',
            'status' => 'actif',
        ]);

        $academicYear = AcademicYear::create(['label' => '2026-2027', 'start_date' => '2026-10-01', 'end_date' => '2027-07-31', 'is_current' => true]);
        $academicLevel = AcademicLevel::create(['name' => 'Terminale', 'cycle' => 'secondaire', 'order' => 7]);
        $classe = Classe::create(['establishment_id' => $establishment->id, 'academic_year_id' => $academicYear->id, 'academic_level_id' => $academicLevel->id, 'name' => 'Terminale D', 'capacity' => 50]);

        $student = Student::create([
            'establishment_id' => $establishment->id,
            'class_id' => $classe->id,
            'matricule' => 'STU-0001',
            'first_name' => 'Awa',
            'last_name' => 'Deby',
            'birth_date' => '2008-01-01',
            'sex' => 'F',
        ]);

        $examType = ExamType::create(['code' => 'BAC', 'name' => 'Baccalauréat', 'cycle' => 'secondaire']);
        $examSession = ExamSession::create([
            'exam_type_id' => $examType->id,
            'academic_year_id' => $academicYear->id,
            'label' => 'Baccalauréat session 2027',
            'registration_start' => '2026-11-01',
            'registration_end' => '2027-01-31',
            'exam_date_start' => '2027-07-05',
            'exam_date_end' => '2027-07-10',
            'status' => 'ouverte',
        ]);

        $this->application = Application::create([
            'student_id' => $student->id,
            'establishment_id' => $establishment->id,
            'exam_session_id' => $examSession->id,
            'status' => Application::STATUS_BROUILLON,
        ]);

        $this->etablissementUser = User::create(['name' => 'Directeur', 'email' => 'dir@test.com', 'password' => 'x', 'establishment_id' => $establishment->id]);
        $this->departementUser = User::create(['name' => 'Dept', 'email' => 'dept@test.com', 'password' => 'x', 'department_id' => $department->id]);
        $this->regionUser = User::create(['name' => 'Region', 'email' => 'region@test.com', 'password' => 'x', 'region_id' => $region->id]);
        $this->nationalUser = User::create(['name' => 'National', 'email' => 'national@test.com', 'password' => 'x']);

        // Utilisateur régional d'une AUTRE région, pour vérifier l'isolation de périmètre.
        User::create(['name' => 'Autre Region', 'email' => 'autreregion@test.com', 'password' => 'x', 'region_id' => $autreRegion->id]);
    }

    public function test_full_happy_path_through_all_three_control_levels(): void
    {
        $app = $this->service->submit($this->application, $this->etablissementUser);
        $this->assertSame(Application::STATUS_SOUMISE, $app->status);

        $app = $this->service->approve($app, $this->departementUser);
        $this->assertSame(Application::STATUS_VALIDEE_DEPARTEMENT, $app->status);

        $app = $this->service->approve($app, $this->regionUser);
        $this->assertSame(Application::STATUS_VALIDEE_REGION, $app->status);

        $app = $this->service->approve($app, $this->nationalUser);
        $this->assertSame(Application::STATUS_INSCRIT, $app->status);

        $this->assertCount(4, $app->statusHistories);
    }

    public function test_departmental_user_cannot_skip_ahead_to_regional_step(): void
    {
        $this->service->submit($this->application, $this->etablissementUser);

        $this->expectException(InvalidApplicationTransitionException::class);
        // Le dossier est en 'soumise' (étape départementale) : un compte régional ne peut pas agir dessus.
        $this->service->approve($this->application->fresh(), $this->regionUser);
    }

    public function test_regional_user_from_a_different_region_is_rejected(): void
    {
        $app = $this->service->submit($this->application, $this->etablissementUser);
        $app = $this->service->approve($app, $this->departementUser);

        $autreRegion = User::where('email', 'autreregion@test.com')->first();

        $this->expectException(InvalidApplicationTransitionException::class);
        $this->service->approve($app, $autreRegion);
    }

    public function test_correction_cycle_returns_to_the_requesting_level(): void
    {
        $app = $this->service->submit($this->application, $this->etablissementUser);
        $app = $this->service->approve($app, $this->departementUser);

        // Le régional demande une correction.
        $app = $this->service->requestCorrection($app, $this->regionUser, 'Pièce manquante');
        $this->assertSame(Application::STATUS_A_CORRIGER_REGION, $app->status);
        $this->assertSame('Pièce manquante', $app->rejection_reason);

        // L'établissement corrige et resoumet : retour direct au régional (pas au départemental).
        $app = $this->service->resubmit($app, $this->etablissementUser);
        $this->assertSame(Application::STATUS_VALIDEE_DEPARTEMENT, $app->status);

        $app = $this->service->approve($app, $this->regionUser);
        $this->assertSame(Application::STATUS_VALIDEE_REGION, $app->status);
    }

    public function test_correction_request_without_comment_is_rejected(): void
    {
        $app = $this->service->submit($this->application, $this->etablissementUser);

        $this->expectException(InvalidApplicationTransitionException::class);
        $this->service->requestCorrection($app, $this->departementUser, '   ');
    }

    public function test_rejection_is_definitive_and_requires_a_reason(): void
    {
        $app = $this->service->submit($this->application, $this->etablissementUser);
        $app = $this->service->reject($app, $this->departementUser, 'Doublon détecté');

        $this->assertSame(Application::STATUS_REJETEE, $app->status);
        $this->assertTrue($app->isTerminal());

        $this->expectException(InvalidApplicationTransitionException::class);
        $this->service->approve($app, $this->nationalUser);
    }

    public function test_national_user_can_act_at_any_level(): void
    {
        $app = $this->service->submit($this->application, $this->etablissementUser);
        $app = $this->service->approve($app, $this->nationalUser);
        $this->assertSame(Application::STATUS_VALIDEE_DEPARTEMENT, $app->status);
    }

    public function test_only_owning_establishment_can_submit(): void
    {
        $autreEtablissement = Establishment::create([
            'region_id' => $this->departementUser->department->region_id,
            'department_id' => $this->departementUser->department_id,
            'code' => 'MOU-LYC-002',
            'name' => 'Autre lycée',
            'type' => 'public',
            'status' => 'actif',
        ]);
        $autreEtablissementUser = User::create(['name' => 'Autre', 'email' => 'autre@test.com', 'password' => 'x', 'establishment_id' => $autreEtablissement->id]);

        $this->expectException(InvalidApplicationTransitionException::class);
        $this->service->submit($this->application, $autreEtablissementUser);
    }

    public function test_cannot_cancel_a_terminal_application(): void
    {
        $app = $this->service->submit($this->application, $this->etablissementUser);
        $app = $this->service->reject($app, $this->departementUser, 'Motif');

        $this->expectException(InvalidApplicationTransitionException::class);
        $this->service->cancel($app, $this->etablissementUser);
    }
}
