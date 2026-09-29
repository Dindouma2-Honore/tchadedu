# Hotfix : crash du dashboard quand aucun dossier n'est rejeté/à corriger

## Le bug

Le widget "Derniers rejets et corrections demandées" plantait avec :
```
LogicException: Unable to create query for empty collection.
```
dès que la base ne contenait **aucun** dossier rejeté ou en correction — ce
qui est le cas sur une base fraîchement seedée. `Collection::toQuery()`
(appelé sur une collection déjà chargée en mémoire) refuse explicitement de
fonctionner sur une collection vide, faute de clés à mettre dans un
`WHERE IN`.

## Le correctif

Le widget donne maintenant directement un `Builder` (requête non exécutée)
à Filament au lieu de charger la collection puis de la reconvertir en
requête. Fonctionne uniformément, qu'il y ait 0 ou 1000 résultats.

## Fichiers du lot

- `app/Filament/Widgets/RecentRejectionsTable.php` — remplace.
- `app/Services/ApplicationStatisticsService.php` — remplace (ajoute
  `recentRejectionsAndCorrectionsQuery()`, garde l'ancienne méthode pour
  compatibilité).
- `tests/Feature/RecentRejectionsWidgetBugTest.php` — nouveau, reproduit
  exactement le crash signalé puis vérifie qu'il ne se produit plus.

## Application

```bash
composer dump-autoload
php artisan test --filter=RecentRejectionsWidgetBugTest   # 2 passed
php artisan test                                           # 64 passed
```
