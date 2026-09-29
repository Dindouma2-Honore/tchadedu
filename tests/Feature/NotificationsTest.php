<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Console\Commands\SendExamSessionDeadlineReminders;
use App\Models\Application;
use App\Models\ExamSession;
use App\Models\NotificationLog;
use App\Models\Student;
use App\Models\User;
use App\Notifications\ApplicationStatusChanged;
use App\Notifications\ExamSessionDeadlineApproaching;
use App\Services\ApplicationWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotificationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_submitting_an_application_notifies_the_departmental_level_but_not_the_regional_level(): void
    {
        Notification::fake();

        $etab = User::where('email', 'eboue@tchadedu.com')->firstOrFail();
        $dept = User::where('email', 'ndjamena@tchadedu.com')->firstOrFail();
        $region = User::where('email', 'occidental@tchadedu.com')->firstOrFail();

        $app = Application::where('establishment_id', $etab->establishment_id)
            ->where('status', Application::STATUS_BROUILLON)
            ->first() ?? $this->createDraftApplication($etab);

        app(ApplicationWorkflowService::class)->submit($app, $etab);

        Notification::assertSentTo($etab, ApplicationStatusChanged::class);
        Notification::assertSentTo($dept, ApplicationStatusChanged::class);
        Notification::assertNotSentTo($region, ApplicationStatusChanged::class);
    }

    public function test_correction_request_logs_mail_database_and_sms_channels_when_contact_info_is_available(): void
    {
        $etab = User::where('email', 'eboue@tchadedu.com')->firstOrFail();
        $etab->update(['phone' => '+23566000000']);
        $dept = User::where('email', 'ndjamena@tchadedu.com')->firstOrFail();

        $app = Application::where('status', Application::STATUS_SOUMISE)->firstOrFail();

        app(ApplicationWorkflowService::class)->requestCorrection($app, $dept, 'Photo illisible');

        $logs = NotificationLog::where('notifiable_id', $etab->id)
            ->where('notifiable_type', User::class)
            ->pluck('channel')
            ->sort()
            ->values();

        $this->assertSame(['database', 'mail', 'sms'], $logs->all());

        $smsLog = NotificationLog::where('notifiable_id', $etab->id)->where('channel', 'sms')->firstOrFail();
        $this->assertSame('sent', $smsLog->status);
        $this->assertStringContainsString('Photo illisible', $smsLog->body);
    }

    public function test_sms_is_not_sent_for_a_routine_approval(): void
    {
        $etab = User::where('email', 'eboue@tchadedu.com')->firstOrFail();
        $etab->update(['phone' => '+23566000000']);
        $dept = User::where('email', 'ndjamena@tchadedu.com')->firstOrFail();

        $app = Application::where('status', Application::STATUS_SOUMISE)->firstOrFail();

        app(ApplicationWorkflowService::class)->approve($app, $dept);

        $this->assertSame(
            0,
            NotificationLog::where('notifiable_id', $etab->id)->where('channel', 'sms')->count()
        );
        $this->assertGreaterThan(
            0,
            NotificationLog::where('notifiable_id', $etab->id)->where('channel', 'mail')->count()
        );
    }

    public function test_database_notification_bell_receives_the_entry(): void
    {
        $etab = User::where('email', 'eboue@tchadedu.com')->firstOrFail();
        $dept = User::where('email', 'ndjamena@tchadedu.com')->firstOrFail();

        $app = Application::where('status', Application::STATUS_SOUMISE)->firstOrFail();

        app(ApplicationWorkflowService::class)->approve($app, $dept);

        $notification = $etab->fresh()->notifications()->first();

        $this->assertNotNull($notification);
        $this->assertSame(ApplicationStatusChanged::class, $notification->type);
        $this->assertSame($app->id, $notification->data['application_id']);
    }

    public function test_deadline_reminder_command_notifies_only_establishments_with_draft_applications(): void
    {
        Notification::fake();

        $etab = User::where('email', 'eboue@tchadedu.com')->firstOrFail();

        $session = ExamSession::first();
        $session->update([
            'status' => 'ouverte',
            'registration_end' => now()->addDays(3),
        ]);

        $this->createDraftApplication($etab, $session);

        $this->artisan(SendExamSessionDeadlineReminders::class)->assertSuccessful();

        Notification::assertSentTo($etab, ExamSessionDeadlineApproaching::class);
    }

    public function test_deadline_reminder_command_does_not_notify_when_no_drafts_are_pending(): void
    {
        Notification::fake();

        $etab = User::where('email', 'eboue@tchadedu.com')->firstOrFail();

        $session = ExamSession::first();
        $session->update([
            'status' => 'ouverte',
            'registration_end' => now()->addDays(3),
        ]);
        // Toutes les candidatures seedées pour cette session sont déjà "soumise", pas "brouillon".

        $this->artisan(SendExamSessionDeadlineReminders::class)->assertSuccessful();

        Notification::assertNotSentTo($etab, ExamSessionDeadlineApproaching::class);
    }

    private function createDraftApplication(User $etab, ?ExamSession $session = null): Application
    {
        $session ??= ExamSession::firstOrFail();

        $student = Student::create([
            'establishment_id' => $etab->establishment_id,
            'last_name' => 'Brouillon',
            'first_name' => 'Test',
            'birth_date' => '2009-01-01',
            'sex' => 'M',
        ]);

        return Application::create([
            'student_id' => $student->id,
            'establishment_id' => $etab->establishment_id,
            'exam_session_id' => $session->id,
            'status' => Application::STATUS_BROUILLON,
        ]);
    }
}
