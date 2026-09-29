<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Application;
use App\Models\ApplicationStatusHistory;
use App\Notifications\Channels\SmsChannel;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notifie un changement de statut de candidature (module "Notifications"
 * du diagramme : SMS / E-mail / Notifications internes). Envoyée par
 * ApplicationStatusHistoryObserver à chaque étape du workflow — voir
 * ApplicationWorkflowService pour la liste des transitions possibles.
 *
 * Volontairement SANS ShouldQueue : le projet n'a pas de worker de file
 * d'attente garanti actif partout où ce module tourne (QUEUE_CONNECTION
 * peut varier selon l'environnement), et une notification de workflow
 * doit rester visible immédiatement. À revoir si le volume de candidatures
 * rend l'envoi synchrone trop lent pour l'utilisateur qui déclenche la
 * transition (auquel cas : ajouter `implements ShouldQueue` ici une fois
 * qu'un worker tourne en permanence).
 */
class ApplicationStatusChanged extends Notification
{
    /** Statuts jugés assez importants pour justifier un SMS (payant, donc à ne pas envoyer à chaque étape intermédiaire). */
    private const SMS_WORTHY_STATUSES = [
        Application::STATUS_A_CORRIGER_DEPARTEMENT,
        Application::STATUS_A_CORRIGER_REGION,
        Application::STATUS_A_CORRIGER_NATIONAL,
        Application::STATUS_INSCRIT,
        Application::STATUS_REJETEE,
    ];

    public function __construct(private readonly ApplicationStatusHistory $history) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if (filled($notifiable->email ?? null)) {
            $channels[] = 'mail';
        }

        if (filled($notifiable->phone ?? null) && in_array($this->history->to_status, self::SMS_WORTHY_STATUSES, true)) {
            $channels[] = SmsChannel::class;
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $application = $this->history->application;

        return (new MailMessage)
            ->subject('Candidature '.$application->reference.' : '.$application->statusLabel())
            ->greeting('Bonjour '.$notifiable->name.',')
            ->line($this->summarySentence())
            ->when($this->history->comment, fn (MailMessage $mail) => $mail->line('Commentaire : '.$this->history->comment))
            ->line('Référence du dossier : '.$application->reference)
            ->salutation('Tchad Éducation Digital');
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        $application = $this->history->application;

        return [
            'application_id' => $application->id,
            'application_reference' => $application->reference,
            'from_status' => $this->history->from_status,
            'to_status' => $this->history->to_status,
            'status_label' => $application->statusLabel(),
            'comment' => $this->history->comment,
            'message' => $this->summarySentence(),
        ];
    }

    public function toSms(object $notifiable): string
    {
        $application = $this->history->application;

        return sprintf(
            'TchadEdu : dossier %s -> %s.%s',
            $application->reference,
            $application->statusLabel(),
            $this->history->comment ? ' Motif : '.$this->history->comment : ''
        );
    }

    public function logSubject(): string
    {
        return 'Candidature '.$this->history->application->reference.' : '.$this->history->application->statusLabel();
    }

    public function logBody(): string
    {
        return $this->summarySentence().($this->history->comment ? ' — '.$this->history->comment : '');
    }

    public function relatedApplicationId(): int
    {
        return $this->history->application_id;
    }

    private function summarySentence(): string
    {
        $application = $this->history->application;

        return sprintf(
            'Le dossier %s est passé au statut « %s ».',
            $application->reference,
            $application->statusLabel()
        );
    }
}
