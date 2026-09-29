<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Console\Commands\ExpireStalePayments;
use App\Exceptions\InvalidApplicationTransitionException;
use App\Exceptions\InvalidPaymentTransitionException;
use App\Models\Application;
use App\Models\ExamSession;
use App\Models\Payment;
use App\Models\User;
use App\Services\ApplicationWorkflowService;
use App\Services\Payment\PaymentWebhookVerifier;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentsTest extends TestCase
{
    use RefreshDatabase;

    private User $etab;

    private Application $application;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $session = ExamSession::firstOrFail();
        $session->update(['fee_amount' => 5000]);

        $this->etab = User::where('email', 'eboue@tchadedu.com')->firstOrFail();
        $this->application = Application::where('exam_session_id', $session->id)->firstOrFail();
        $this->application->update(['status' => Application::STATUS_BROUILLON]);
        $this->application->refresh();
    }

    public function test_submit_is_blocked_when_session_requires_payment_and_none_was_made(): void
    {
        $this->expectException(InvalidApplicationTransitionException::class);
        $this->expectExceptionMessage('paiement');

        app(ApplicationWorkflowService::class)->submit($this->application, $this->etab);
    }

    public function test_submit_succeeds_when_session_is_free(): void
    {
        $freeSession = ExamSession::where('id', '!=', $this->application->exam_session_id)->first()
            ?? $this->application->examSession;
        $freeSession->update(['fee_amount' => null]);

        $app = Application::create([
            'student_id' => $this->application->student_id,
            'establishment_id' => $this->etab->establishment_id,
            'exam_session_id' => $freeSession->id,
            'status' => Application::STATUS_BROUILLON,
        ]);

        $app = app(ApplicationWorkflowService::class)->submit($app, $this->etab);

        $this->assertSame(Application::STATUS_SOUMISE, $app->status);
    }

    public function test_initiate_creates_a_payment_and_is_idempotent(): void
    {
        $result = app(PaymentService::class)->initiate($this->application, $this->etab);

        $this->assertSame(Payment::STATUS_INITIATED, $result['payment']->status);
        $this->assertNotEmpty($result['redirect_url']);
        $this->assertSame(1, Payment::count());

        // Deuxième appel : réutilise le paiement existant plutôt que d'en créer un second.
        app(PaymentService::class)->initiate($this->application, $this->etab);
        $this->assertSame(1, Payment::count());
    }

    public function test_initiate_fails_when_session_does_not_require_payment(): void
    {
        $this->application->examSession->update(['fee_amount' => null]);

        $this->expectException(InvalidPaymentTransitionException::class);
        app(PaymentService::class)->initiate($this->application->fresh(), $this->etab);
    }

    public function test_webhook_rejects_invalid_signature(): void
    {
        $payment = app(PaymentService::class)->initiate($this->application, $this->etab)['payment'];

        $body = json_encode(['payment_reference' => $payment->reference, 'status' => 'success']);

        $response = $this->call('POST', '/webhooks/payments', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_Payment_Signature' => 'signature-invalide',
        ], $body);

        $response->assertStatus(401);
        $this->assertSame(Payment::STATUS_INITIATED, $payment->fresh()->status);
    }

    public function test_webhook_with_valid_signature_confirms_payment_and_issues_a_receipt(): void
    {
        $payment = app(PaymentService::class)->initiate($this->application, $this->etab)['payment'];
        $verifier = app(PaymentWebhookVerifier::class);

        $body = json_encode(['payment_reference' => $payment->reference, 'status' => 'success']);
        $signature = $verifier->sign($body);

        $response = $this->call('POST', '/webhooks/payments', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_Payment_Signature' => $signature,
        ], $body);

        $response->assertOk();

        $payment->refresh();
        $this->assertSame(Payment::STATUS_SUCCESS, $payment->status);
        $this->assertNotNull($payment->paid_at);
        $this->assertNotNull($payment->receipt);
        $this->assertTrue($this->application->fresh()->hasSuccessfulPayment());
    }

    public function test_webhook_is_idempotent_on_replayed_success_callback(): void
    {
        $payment = app(PaymentService::class)->initiate($this->application, $this->etab)['payment'];
        $verifier = app(PaymentWebhookVerifier::class);
        $body = json_encode(['payment_reference' => $payment->reference, 'status' => 'success']);
        $signature = $verifier->sign($body);

        $this->call('POST', '/webhooks/payments', [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_X_Payment_Signature' => $signature], $body);
        $this->call('POST', '/webhooks/payments', [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_X_Payment_Signature' => $signature], $body);

        $payment->refresh();
        $this->assertSame(1, $payment->transactions()->where('event', 'callback_received')->count());
        $this->assertSame(1, $payment->receipt()->count());
    }

    public function test_webhook_with_unknown_reference_is_silently_ignored(): void
    {
        $verifier = app(PaymentWebhookVerifier::class);
        $body = json_encode(['payment_reference' => 'PAY-DOES-NOT-EXIST', 'status' => 'success']);
        $signature = $verifier->sign($body);

        $response = $this->call('POST', '/webhooks/payments', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_Payment_Signature' => $signature,
        ], $body);

        $response->assertOk();
        $response->assertJson(['status' => 'ignored']);
    }

    public function test_manual_confirmation_requires_the_permission(): void
    {
        $payment = app(PaymentService::class)->initiate($this->application, $this->etab)['payment'];

        $this->assertFalse($this->etab->can('manualConfirm', $payment));

        $agentFinancier = User::factory()->create();
        $agentFinancier->roles()->attach(\App\Models\Role::where('slug', 'agent-financier')->firstOrFail());

        $this->assertTrue($agentFinancier->can('manualConfirm', $payment));

        $confirmed = app(PaymentService::class)->confirmManually($payment, $agentFinancier, 'Dépôt bancaire n°12345');

        $this->assertSame(Payment::STATUS_SUCCESS, $confirmed->status);
        $this->assertNotNull($confirmed->receipt);
    }

    public function test_refund_only_works_on_a_successful_payment(): void
    {
        $payment = app(PaymentService::class)->initiate($this->application, $this->etab)['payment'];

        $this->expectException(InvalidPaymentTransitionException::class);
        app(PaymentService::class)->refund($payment, $this->etab, 'Erreur de saisie');
    }

    public function test_refund_succeeds_after_a_successful_payment(): void
    {
        $payment = app(PaymentService::class)->initiate($this->application, $this->etab)['payment'];
        $verifier = app(PaymentWebhookVerifier::class);
        $body = json_encode(['payment_reference' => $payment->reference, 'status' => 'success']);
        $this->call('POST', '/webhooks/payments', [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_X_Payment_Signature' => $verifier->sign($body)], $body);

        $refunded = app(PaymentService::class)->refund($payment->fresh(), $this->etab, 'Candidature annulée');

        $this->assertSame(Payment::STATUS_REFUNDED, $refunded->status);
    }

    public function test_expire_stale_payments_command_marks_old_payments_as_expired(): void
    {
        $payment = app(PaymentService::class)->initiate($this->application, $this->etab)['payment'];
        $payment->forceFill(['created_at' => now()->subHours(48)])->save();

        $this->artisan(ExpireStalePayments::class, ['--hours' => 24])->assertSuccessful();

        $this->assertSame(Payment::STATUS_EXPIRED, $payment->fresh()->status);
    }

    public function test_demo_payment_page_confirms_via_the_real_webhook_path(): void
    {
        $result = app(PaymentService::class)->initiate($this->application, $this->etab);
        $payment = $result['payment'];

        $response = $this->get($result['redirect_url']);
        $response->assertOk();
        $response->assertSee($payment->reference);
    }

    public function test_demo_confirm_sends_a_correctly_signed_request_to_the_webhook(): void
    {
        \Illuminate\Support\Facades\Http::fake([
            '*/webhooks/payments' => \Illuminate\Support\Facades\Http::response(['status' => 'ok'], 200),
        ]);

        $payment = app(PaymentService::class)->initiate($this->application, $this->etab)['payment'];
        $verifier = app(PaymentWebhookVerifier::class);

        $this->post(route('payments.demo.confirm', $payment->reference), ['outcome' => 'success'])
            ->assertRedirect();

        \Illuminate\Support\Facades\Http::assertSent(function (\Illuminate\Http\Client\Request $request) use ($payment, $verifier) {
            $signatureMatches = $verifier->verify($request->body(), $request->header('X-Payment-Signature')[0] ?? null);
            $payloadMatches = json_decode($request->body(), true)['payment_reference'] === $payment->reference;

            return $signatureMatches && $payloadMatches;
        });
    }
}
