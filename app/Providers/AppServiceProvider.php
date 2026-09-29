<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\PaymentGateway;
use App\Contracts\SmsGateway;
use App\Models\Application;
use App\Models\ApplicationStatusHistory;
use App\Models\NotificationLog;
use App\Models\User;
use App\Observers\ApplicationObserver;
use App\Observers\ApplicationStatusHistoryObserver;
use App\Observers\UserScopeObserver;
use App\Services\Payment\CinetPayGateway;
use App\Services\Payment\PaymentWebhookVerifier;
use App\Services\Payment\SimulatedPaymentGateway;
use App\Services\Sms\LogSmsGateway;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Voir LogSmsGateway : à remplacer par un vrai opérateur avant mise en production.
        $this->app->bind(SmsGateway::class, LogSmsGateway::class);

        // Bascule automatique vers le vrai prestataire CinetPay dès que ses
        // identifiants sont configurés (voir config/services.php) ; sinon,
        // reste sur SimulatedPaymentGateway pour le développement local.
        $this->app->bind(PaymentGateway::class, function () {
            $apiKey = config('services.cinetpay.apikey');
            $siteId = config('services.cinetpay.site_id');

            if (filled($apiKey) && filled($siteId)) {
                return new CinetPayGateway($apiKey, $siteId);
            }

            return new SimulatedPaymentGateway;
        });

        $this->app->singleton(PaymentWebhookVerifier::class, fn () => new PaymentWebhookVerifier(
            (string) config('services.payment_webhook.secret')
        ));
    }

    public function boot(): void
    {
        User::observe(UserScopeObserver::class);
        Application::observe(ApplicationObserver::class);
        ApplicationStatusHistory::observe(ApplicationStatusHistoryObserver::class);

        $this->logSentNotifications();
    }

    /**
     * Journalise dans NotificationLog les canaux "mail" et "database" pour
     * toute notification qui expose logSubject()/logBody() (opt-in par
     * duck-typing : les notifications Filament internes, non concernées,
     * n'implémentent pas ces méthodes et sont donc ignorées ici).
     *
     * Le canal "sms" se journalise lui-même dans SmsChannel — voir son
     * commentaire pour la raison (gérer aussi l'échec, pas seulement le succès).
     */
    private function logSentNotifications(): void
    {
        Event::listen(NotificationSent::class, function (NotificationSent $event): void {
            if (! in_array($event->channel, ['mail', 'database'], true)) {
                return;
            }

            if (! method_exists($event->notification, 'logBody')) {
                return;
            }

            NotificationLog::create([
                'notifiable_type' => $event->notifiable::class,
                'notifiable_id' => $event->notifiable->getKey(),
                'application_id' => method_exists($event->notification, 'relatedApplicationId')
                    ? $event->notification->relatedApplicationId()
                    : null,
                'channel' => $event->channel,
                'subject' => method_exists($event->notification, 'logSubject') ? $event->notification->logSubject() : null,
                'body' => $event->notification->logBody(),
                'status' => 'sent',
            ]);
        });
    }
}
