<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\ExamSession;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * "Alerte système" du module Notifications du diagramme : prévient les
 * établissements ayant des dossiers restés en brouillon qu'une échéance de
 * dépôt approche. Déclenchée par la commande
 * App\Console\Commands\SendExamSessionDeadlineReminders, planifiée dans
 * routes/console.php.
 */
class ExamSessionDeadlineApproaching extends Notification
{
    public function __construct(private readonly ExamSession $session, private readonly int $draftCount) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if (filled($notifiable->email ?? null)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Échéance proche : '.$this->session->label)
            ->greeting('Bonjour '.$notifiable->name.',')
            ->line($this->summarySentence())
            ->line('Date limite de dépôt : '.$this->session->registration_end->translatedFormat('d F Y'))
            ->line("Pensez à finaliser et soumettre les dossiers concernés avant cette date : passée, ils ne pourront plus être déposés pour cette session.")
            ->salutation('Tchad Éducation Digital');
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'exam_session_id' => $this->session->id,
            'exam_session_label' => $this->session->label,
            'registration_end' => $this->session->registration_end->toDateString(),
            'draft_count' => $this->draftCount,
            'message' => $this->summarySentence(),
        ];
    }

    public function logSubject(): string
    {
        return 'Échéance proche : '.$this->session->label;
    }

    public function logBody(): string
    {
        return $this->summarySentence();
    }

    private function summarySentence(): string
    {
        return sprintf(
            "%d dossier(s) de votre établissement sont encore en brouillon pour « %s », dont la date limite de dépôt est le %s.",
            $this->draftCount,
            $this->session->label,
            $this->session->registration_end->translatedFormat('d F Y')
        );
    }
}
