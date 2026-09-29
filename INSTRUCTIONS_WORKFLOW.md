# Workflow de validation multi-niveaux — Instructions d'application

Ce lot ajoute le contrôle hiérarchique à 3 niveaux (Départemental → Régional →
National) à la place du workflow MVP à plat. Tous les fichiers ci-dessous
respectent l'arborescence de votre projet `tchadEdu` : il suffit de les copier
par-dessus (aucun autre fichier du projet n'est touché).

## Fichiers du lot

- `app/Models/Application.php` — remplace le fichier existant (statuts,
  constantes CONTROL_STEPS/RESUBMIT_TARGETS, helpers).
- `app/Policies/ApplicationPolicy.php` — remplace le fichier existant.
- `app/Services/ApplicationWorkflowService.php` — nouveau.
- `app/Exceptions/InvalidApplicationTransitionException.php` — nouveau.
- `app/Filament/Resources/ApplicationResource.php` — remplace le fichier existant.
- `app/Filament/Resources/ApplicationResource/Pages/EditApplication.php` — remplace.
- `app/Filament/Resources/ApplicationResource/RelationManagers/StatusHistoriesRelationManager.php` — nouveau.
- `database/migrations/2026_09_07_000001_extend_applications_status_workflow.php` — nouveau.
- `database/seeders/PermissionSeeder.php` — remplace (ajoute `applications.cancel`).
- `database/seeders/RolePermissionSeeder.php` — remplace (attribue `applications.cancel`).
- `tests/Feature/ApplicationWorkflowTest.php` — nouveau (9 tests, tous verts).

## Étapes à exécuter après copie

```bash
php artisan migrate            # applique la nouvelle contrainte de statut
php artisan db:seed --class=PermissionSeeder
php artisan db:seed --class=RolePermissionSeeder
php artisan test --filter=ApplicationWorkflowTest   # doit afficher 9 passed
```

Si vous repartez d'une base vierge (`migrate:fresh --seed`), rien de plus n'est
nécessaire : tous les seeders sont déjà à jour.

## Ce qui change pour l'utilisateur final (Filament)

Sur chaque candidature, selon son statut et le rôle de la personne connectée,
de nouveaux boutons d'action apparaissent (en ligne de tableau et sur la
fiche) : **Soumettre**, **Approuver**, **Renvoyer en correction**,
**Resoumettre**, **Rejeter**, **Annuler**. Un onglet **Historique du dossier**
apparaît sur la fiche pour tracer chaque changement de statut, qui l'a fait
et pourquoi.

## Statuts (nouveau schéma)

```
brouillon
  → soumise                      (dépôt établissement)
    → a_corriger_departement     (retour départemental, motif obligatoire)
    → validee_departement        (approbation départementale)
      → a_corriger_region        (retour régional, motif obligatoire)
      → validee_region           (approbation régionale)
        → a_corriger_national    (retour national, motif obligatoire)
        → inscrit                (approbation nationale = fin du circuit)
(+ rejetee et annulee, accessibles à toute étape de contrôle / avant clôture)
```

Un dossier `a_corriger_X` redémarre, après resoumission par l'établissement,
directement à l'étape `X` (pas de retour au tout début) : il ne repasse pas
par les niveaux déjà validés.

## Points de sécurité vérifiés (voir tests)

- Un compte départemental/régional ne peut agir qu'à l'étape qui le
  concerne — impossible de "sauter" un niveau.
- Un compte régional/départemental hors du bon département/région est
  bloqué (testé avec de vraies données seedées sur MariaDB).
- Un compte national peut agir à n'importe quel niveau (supervision globale),
  mais chaque action reste tracée avec son identité dans l'historique.
- Motif obligatoire pour toute correction ou tout rejet.
- Un dossier `inscrit`, `rejetee` ou `annulee` est définitivement clos.
