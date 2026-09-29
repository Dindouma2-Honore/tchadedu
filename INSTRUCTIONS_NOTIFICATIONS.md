# Notifications (SMS / E-mail / Internes / Alertes système) — Instructions

## Fichiers du lot

**Nouveaux :**
- `app/Contracts/SmsGateway.php` — interface indépendante de tout opérateur.
- `app/Services/Sms/LogSmsGateway.php` — implémentation par défaut (journalise, n'appelle aucun opérateur réel — voir "Brancher un vrai opérateur SMS" ci-dessous).
- `app/Notifications/Channels/SmsChannel.php` — canal de notification "sms" pour le système Laravel.
- `app/Notifications/ApplicationStatusChanged.php` — notification envoyée à chaque transition de statut d'une candidature.
- `app/Notifications/ExamSessionDeadlineApproaching.php` — notification de rappel d'échéance.
- `app/Observers/ApplicationStatusHistoryObserver.php` — détermine qui doit être notifié à chaque changement de statut.
- `app/Console/Commands/SendExamSessionDeadlineReminders.php` — commande planifiée quotidienne.
- `app/Models/NotificationLog.php` — journal de toutes les notifications envoyées, tous canaux.
- `database/migrations/2026_09_11_000001_create_notifications_table.php` — table standard Laravel (canal "database" / cloche).
- `database/migrations/2026_09_11_000002_create_notification_logs_table.php` — table d'audit.
- `tests/Feature/NotificationsTest.php`, `tests/Feature/NotificationBellSmokeTest.php` — 7 tests.

**Remplacés :**
- `app/Providers/AppServiceProvider.php` — lie `SmsGateway`, enregistre l'observer, journalise mail/database via un listener sur `NotificationSent`.
- `app/Providers/Filament/AdminPanelProvider.php` — active `->databaseNotifications()` (cloche dans le panel).
- `routes/console.php` — planifie `applications:remind-deadlines` tous les jours à 7h.

## Étapes après copie

```bash
php artisan migrate
php artisan test --filter=NotificationsTest          # 6 passed
php artisan test --filter=NotificationBellSmokeTest  # 1 passed
php artisan test                                      # suite complète, doit rester verte
```

Pour que la commande planifiée s'exécute réellement en production, une tâche
cron standard Laravel doit tourner (si ce n'est pas déjà le cas) :
```
* * * * * cd /chemin/vers/le/projet && php artisan schedule:run >> /dev/null 2>&1
```

## Comment ça fonctionne

**Déclenchement automatique.** Chaque fois qu'une candidature change de
statut (soumission, approbation, correction, rejet, resoumission,
annulation — tout ce qui passe par `ApplicationWorkflowService`), une
`ApplicationStatusHistory` est créée, ce qui déclenche automatiquement
`ApplicationStatusHistoryObserver`. Aucune modification n'était nécessaire
dans `ApplicationWorkflowService` lui-même.

**Qui est notifié, à chaque changement :**
1. Tous les comptes actifs de l'établissement propriétaire du dossier.
2. Si le dossier attend une décision à un niveau (départemental, régional,
   national), tous les comptes actifs de ce niveau — pour que le circuit
   ne stagne pas faute d'être vu.
3. Le candidat lui-même, s'il dispose d'un compte (`student_id` renseigné).

**Sur quels canaux :**
- **Base de données (cloche Filament)** : toujours, pour toute personne notifiée.
- **E-mail** : si la personne a une adresse e-mail (cas de tous les comptes `User`).
- **SMS** : uniquement si la personne a un numéro de téléphone **et** que la
  transition est jugée importante (renvoi en correction, rejet, inscription
  définitive) — pas à chaque approbation intermédiaire, pour ne pas
  multiplier les SMS payants sur des étapes routinières. Testé et vérifié :
  une approbation départementale routinière ne déclenche pas de SMS, mais
  déclenche bien un e-mail + une entrée dans la cloche.

**Alertes système.** La commande `applications:remind-deadlines` (planifiée
tous les jours à 7h) repère les sessions d'examen dont la clôture des
dépôts approche (7 jours par défaut, réglable via `--days=N`) et alerte les
établissements ayant des dossiers **encore en brouillon** pour cette
session — c'est-à-dire commencés mais jamais soumis.

## Traçabilité

Chaque notification effectivement envoyée (succès ou échec) est journalisée
dans `notification_logs`, consultable via le modèle `NotificationLog`
(`notifiable`, `application`, `channel`, `status`, `error_message`). C'est
distinct de la table `notifications` de Laravel, qui ne conserve que le
canal "database".

## ⚠️ Brancher un vrai opérateur SMS avant mise en production

`LogSmsGateway` ne fait qu'écrire dans les logs applicatifs — **aucun SMS
n'est réellement envoyé**. C'est un choix assumé : le projet n'avait pas de
contrat opérateur SMS confirmé au moment de l'écriture de ce module, et il
était hors de question d'en coder un en dur sans validation commerciale de
votre part.

Pour brancher un vrai opérateur (Airtel, Tigo, Twilio, Africa's Talking...) :
1. Créer une classe qui implémente `App\Contracts\SmsGateway` (une seule
   méthode : `send(string $to, string $message): void`).
2. Dans `AppServiceProvider::register()`, remplacer
   `LogSmsGateway::class` par cette nouvelle classe.

Aucun autre fichier du projet n'a besoin de changer.

## Limitation connue

Les notifications sont envoyées **de façon synchrone** (pas de
`ShouldQueue`), pour rester fiables même sans worker de file d'attente actif
en permanence. Si le volume de candidatures rend l'envoi synchrone trop
lent pour la personne qui déclenche l'action, ajouter
`implements ShouldQueue` sur `ApplicationStatusChanged` une fois qu'un
worker (`php artisan queue:work`) tourne en continu en production.
