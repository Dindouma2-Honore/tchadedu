<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Application;
use App\Models\ExamSession;
use App\Models\Payment;
use App\Models\User;
use App\Services\Payment\PaymentWebhookVerifier;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PdfExportsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_receipt_pdf_downloads_for_the_owning_establishment(): void
    {
        $receipt = $this->createPaidReceipt();
        $etab = User::where('email', 'eboue@tchadedu.com')->firstOrFail();

        $response = $this->actingAs($etab)->get(route('receipts.download', $receipt));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_receipt_pdf_is_forbidden_for_a_user_outside_the_perimeter(): void
    {
        $receipt = $this->createPaidReceipt();
        $regionUser = User::where('email', 'occidental@tchadedu.com')->firstOrFail();

        $this->actingAs($regionUser)->get(route('receipts.download', $receipt))->assertForbidden();
    }

    public function test_receipt_pdf_redirects_to_login_when_unauthenticated(): void
    {
        $receipt = $this->createPaidReceipt();

        $this->get(route('receipts.download', $receipt))->assertRedirect(route('filament.admin.auth.login'));
    }

    public function test_report_pdf_export_downloads_successfully(): void
    {
        $national = User::where('email', 'admin@tchadedu.com')->firstOrFail();

        \Livewire\Livewire::actingAs($national)
            ->test(\App\Filament\Pages\Reports::class)
            ->assertSuccessful()
            ->callAction('exportPdf')
            ->assertFileDownloaded();
    }

    private function createPaidReceipt()
    {
        $session = ExamSession::firstOrFail();
        $session->update(['fee_amount' => 5000]);

        $etab = User::where('email', 'eboue@tchadedu.com')->firstOrFail();
        $application = Application::where('exam_session_id', $session->id)->firstOrFail();

        $payment = app(PaymentService::class)->initiate($application, $etab)['payment'];
        $verifier = app(PaymentWebhookVerifier::class);
        $body = json_encode(['payment_reference' => $payment->reference, 'status' => 'success']);

        $this->call('POST', '/webhooks/payments', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_Payment_Signature' => $verifier->sign($body),
        ], $body);

        return $payment->fresh()->receipt;
    }
}
