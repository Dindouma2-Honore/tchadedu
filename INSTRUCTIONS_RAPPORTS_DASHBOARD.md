# Tableaux de bord & Rapports — Instructions d'application

## Fichiers du lot

- `app/Models/User.php` — **remplace** le fichier existant. Ajoute
  `implements FilamentUser` (`canAccessPanel()` basé sur `is_active`) et le
  trait `HasFactory`. **Important : voir "Bug corrigé" ci-dessous.**
- `app/Services/ApplicationStatisticsService.php` — nouveau. Source unique
  des calculs statistiques (compteurs, tendances, délais, répartitions,
  regroupements par entité administrative).
- `app/Filament/Widgets/*.php` (6 fichiers) — nouveaux widgets de tableau
  de bord, auto-découverts par Filament (aucun enregistrement manuel requis).
- `app/Filament/Pages/Reports.php` + `resources/views/filament/pages/reports.blade.php`
  — nouvelle page "Rapports & Statistiques" (filtre par session, export CSV).
- `database/factories/UserFactory.php` — remplace (ajoute `is_active => true`
  par défaut, nécessaire pour que `canAccessPanel()` fonctionne dans les
  seeds/tests/environnements de démo).
- `tests/Feature/ReportsAndDashboardTest.php`,
  `tests/Feature/DashboardRenderSmokeTest.php` — nouveaux (14 tests au total).

## Étapes après copie

```bash
php artisan test --filter=ReportsAndDashboardTest      # 6 passed
php artisan test --filter=DashboardRenderSmokeTest      # 8 passed
php artisan test                                        # suite complète, doit rester verte
```

Aucune migration n'est nécessaire pour ce lot (aucune colonne ajoutée).

## ⚠️ Bug corrigé en cours de route (indépendant de ce lot, mais bloquant)

`App\Models\User` n'implémentait pas l'interface `Filament\Models\Contracts\FilamentUser`.
Conséquence concrète : `Filament\Http\Middleware\Authenticate` n'autorise
l'accès au panel **que si `config('app.env') === 'local'`** quand le modèle
utilisateur n'implémente pas cette interface. En clair : **une fois déployé
en `staging` ou `production` (ou dans une suite de tests, `APP_ENV=testing`),
plus personne n'aurait pu se connecter au panel**, quel que soit son rôle.

Corrigé en ajoutant :
```php
public function canAccessPanel(Panel $panel): bool
{
    return $this->is_active;
}
```
Un compte désactivé (`is_active = false`) est donc maintenant aussi
correctement bloqué à la porte du panel, pas seulement caché dans l'UI.

## Ce qui change pour l'utilisateur final

**Tableau de bord** (`/admin`) : les widgets s'adaptent silencieusement au
rôle connecté — un directeur d'établissement voit ses propres chiffres
(brouillons, en cours, à corriger, inscrits, rejetés), un compte régional ou
national voit en plus le nombre d'établissements, le délai moyen de
traitement, la répartition par examen, l'évolution des dépôts, et (national
uniquement) la répartition filles/garçons. Un tableau des derniers rejets et
corrections demandées apparaît pour tout niveau ayant un pouvoir de
validation.

**Page "Rapports & Statistiques"** (menu "Examens") : filtre par session
d'examen, cartes de synthèse, tableau détaillé qui se regroupe automatiquement
par région (vue nationale), département (vue régionale) ou établissement (vue
départementale), avec taux de validation et délai moyen par ligne. Bouton
"Exporter en CSV" en haut à droite (compatible Excel, encodage UTF-8 avec BOM
pour un affichage correct des accents).

## Limitation connue

L'export est en **CSV** (aucune dépendance externe nécessaire). Un export PDF
nécessiterait une bibliothèque tierce (ex. `barryvdh/laravel-dompdf`) — non
ajoutée ici faute d'accès réseau à Packagist depuis l'environnement où ce lot
a été préparé. Dites-le si vous voulez qu'on l'ajoute : c'est une extension
simple une fois la dépendance installable depuis votre machine.
