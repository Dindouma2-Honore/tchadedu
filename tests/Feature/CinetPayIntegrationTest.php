<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Contracts\PaymentGateway;
use App\Exceptions\InvalidPaymentTransitionException;
use App\Models\Application;
use App\Models\ExamSession;
use App\Models\Payment;
use App\Models\User;
use App\Services\Payment\CinetPayGateway;
use App\Services\Payment\SimulatedPaymentGateway;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CinetPayIntegrationTest extends TestCase
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

    public function test_container_resolves_the_simulated_gateway_when_no_credentials_are_configured(): void
    {
        Config::set('services.cinetpay.apikey', null);
        Config::set('services.cinetpay.site_id', null);

        $this->assertInstanceOf(SimulatedPaymentGateway::class, app(PaymentGateway::class));
    }

    public function test_container_resolves_cinetpay_gateway_once_credentials_are_configured(): void
    {
        Config::set('services.cinetpay.apikey', 'test-key');
        Config::set('services.cinetpay.site_id', 'test-site');

        // Nouveau binding : app() doit être ré-instancié pour relire la config.
        $this->app->forgetInstance(PaymentGateway::class);

        $this->assertInstanceOf(CinetPayGateway::class, app(PaymentGateway::class));
    }

    public function test_initiate_calls_cinetpay_and_stores_the_returned_payment_url(): void
    {
        Http::fake([
            'api-checkout.cinetpay.com/v2/payment' => Http::response([
                'code' => '201',
                'message' => 'CREATED',
                'data' => [
                    'payment_token' => 'TOKEN123',
                    'payment_url' => 'https://checkout.cinetpay.com/payment/TOKEN123',
                ],
            ], 200),
        ]);

        $gateway = new CinetPayGateway('test-key', 'test-site');
        $service = new PaymentService($gateway);

        $result = $service->initiate($this->application, $this->etab);

        $this->assertSame('https://checkout.cinetpay.com/payment/TOKEN123', $result['redirect_url']);
        $this->assertSame('TOKEN123', $result['payment']->provider_reference);

        Http::assertSent(function (HttpRequest $request) {
            return $request->url() === 'https://api-checkout.cinetpay.com/v2/payment'
                && $request['apikey'] === 'test-key'
                && $request['site_id'] === 'test-site'
                && $request['currency'] === 'XAF';
        });
    }

    public function test_initiate_throws_when_cinetpay_rejects_the_request(): void
    {
        Http::fake([
            'api-checkout.cinetpay.com/v2/payment' => Http::response([
                'code' => 'ERROR',
                'message' => 'INVALID_DATA',
                'description' => 'Le montant est invalide',
            ], 200),
        ]);

        $gateway = new CinetPayGateway('test-key', 'test-site');
        $service = new PaymentService($gateway);

        $this->expectException(InvalidPaymentTransitionException::class);
        $service->initiate($this->application, $this->etab);
    }

    public function test_verify_maps_accepted_to_success(): void
    {
        $gateway = new CinetPayGateway('test-key', 'test-site');
        $payment = Payment::factory()->create([
            'application_id' => $this->application->id,
            'reference' => 'PAY-TEST-0001',
        ]);

        Http::fake(['api-checkout.cinetpay.com/v2/payment/check' => Http::response([
            'code' => '00', 'message' => 'SUCCES', 'data' => ['status' => 'ACCEPTED'],
        ], 200)]);

        $this->assertSame(Payment::STATUS_SUCCESS, $gateway->verify($payment));
    }

    public function test_verify_maps_refused_to_failed(): void
    {
        $gateway = new CinetPayGateway('test-key', 'test-site');
        $payment = Payment::factory()->create([
            'application_id' => $this->application->id,
            'reference' => 'PAY-TEST-0011',
        ]);

        Http::fake(['api-checkout.cinetpay.com/v2/payment/check' => Http::response([
            'code' => '00', 'message' => 'SUCCES', 'data' => ['status' => 'REFUSED'],
        ], 200)]);

        $this->assertSame(Payment::STATUS_FAILED, $gateway->verify($payment));
    }

    public function test_verify_maps_waiting_for_customer_to_pending_not_failed(): void
    {
        $gateway = new CinetPayGateway('test-key', 'test-site');
        $payment = Payment::factory()->create([
            'application_id' => $this->application->id,
            'reference' => 'PAY-TEST-0012',
        ]);

        Http::fake(['api-checkout.cinetpay.com/v2/payment/check' => Http::response([
            'code' => '00', 'message' => 'SUCCES', 'data' => ['status' => 'WAITING_FOR_CUSTOMER'],
        ], 200)]);

        $this->assertSame(Payment::STATUS_PENDING, $gateway->verify($payment));
    }

    public function test_cinetpay_webhook_never_trusts_the_body_and_always_calls_verify(): void
    {
        Http::fake([
            'api-checkout.cinetpay.com/v2/payment/check' => Http::response([
                'code' => '00', 'message' => 'SUCCES', 'data' => ['status' => 'ACCEPTED'],
            ], 200),
        ]);

        Config::set('services.cinetpay.apikey', 'test-key');
        Config::set('services.cinetpay.site_id', 'test-site');
        Config::set('services.cinetpay.secret_key', null); // pas de X-TOKEN à vérifier pour ce test
        $this->app->forgetInstance(PaymentGateway::class);

        $payment = Payment::factory()->create([
            'application_id' => $this->application->id,
            'reference' => 'PAY-TEST-0002',
            'status' => Payment::STATUS_INITIATED,
        ]);

        // Le corps annonce un échec MAIS l'API de vérification (mockée ci-dessus) répond ACCEPTED.
        // Le paiement doit finir "success" : la notification n'est jamais la source de vérité.
        $response = $this->post('/webhooks/payments/cinetpay', [
            'cpm_trans_id' => $payment->reference,
            'cpm_error_message' => 'PAYMENT_FAILED',
        ]);

        $response->assertOk();
        $this->assertSame(Payment::STATUS_SUCCESS, $payment->fresh()->status);
    }

    public function test_cinetpay_webhook_rejects_an_invalid_x_token_when_secret_is_configured(): void
    {
        Config::set('services.cinetpay.secret_key', 'super-secret');

        $payment = Payment::factory()->create([
            'application_id' => $this->application->id,
            'reference' => 'PAY-TEST-0003',
        ]);

        $response = $this->call('POST', '/webhooks/payments/cinetpay', [
            'cpm_trans_id' => $payment->reference,
        ], [], [], ['HTTP_X_TOKEN' => 'wrong-token']);

        $response->assertStatus(401);
        $this->assertSame(Payment::STATUS_INITIATED, $payment->fresh()->status);
    }

    public function test_cinetpay_webhook_accepts_a_correctly_computed_x_token(): void
    {
        Config::set('services.cinetpay.secret_key', 'super-secret');
        Config::set('services.cinetpay.apikey', 'test-key');
        Config::set('services.cinetpay.site_id', 'test-site');
        $this->app->forgetInstance(PaymentGateway::class);

        Http::fake([
            'api-checkout.cinetpay.com/v2/payment/check' => Http::response([
                'code' => '00', 'message' => 'SUCCES', 'data' => ['status' => 'ACCEPTED'],
            ], 200),
        ]);

        $payment = Payment::factory()->create([
            'application_id' => $this->application->id,
            'reference' => 'PAY-TEST-0004',
        ]);

        $fields = [
            'cpm_site_id' => 'test-site',
            'cpm_trans_id' => $payment->reference,
            'cpm_trans_date' => '2026-01-01 10:00:00',
            'cpm_amount' => '5000',
            'cpm_currency' => 'XAF',
            'signature' => 'sig',
            'payment_method' => 'OM',
            'cel_phone_num' => '66000000',
            'cpm_phone_prefixe' => '235',
            'cpm_language' => 'fr',
            'cpm_version' => 'V4',
            'cpm_payment_config' => 'SINGLE',
            'cpm_page_action' => 'PAYMENT',
            'cpm_custom' => '',
            'cpm_designation' => 'Frais examen',
            'cpm_error_message' => '',
        ];

        $concatenated = implode('', array_values($fields));
        $token = hash_hmac('sha256', $concatenated, 'super-secret');

        $response = $this->call('POST', '/webhooks/payments/cinetpay', $fields, [], [], ['HTTP_X_TOKEN' => $token]);

        $response->assertOk();
        $this->assertSame(Payment::STATUS_SUCCESS, $payment->fresh()->status);
    }
}
