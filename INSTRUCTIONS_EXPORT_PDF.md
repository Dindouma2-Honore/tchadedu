# Export PDF (reçus de paiement et rapports) — Instructions d'application

## Comment dompdf a été installé sans Packagist

L'environnement où ce lot a été préparé n'a pas accès à `repo.packagist.org`
(liste blanche réseau restreinte). **dompdf et ses 5 dépendances ont donc
été téléchargés directement depuis leurs dépôts GitHub officiels**, à des
versions stables précises, puis enregistrés comme dépôts Composer locaux
de type `"path"` dans `composer.json` (dossier `packages/` à la racine du
projet). Aucun code n'a été écrit à la main pour dompdf lui-même : c'est la
vraie bibliothèque, à la version 3.1.0, simplement obtenue par un autre
canal que Packagist.

**Chez vous, si vous avez un accès normal à Packagist**, vous pouvez tout
simplement ignorer le dossier `packages/` et faire à la place :
```bash
composer remove dompdf/dompdf  # retire la référence "path" existante si besoin
composer require dompdf/dompdf
```
Composer résoudra alors la bibliothèque normalement depuis Packagist, et
vous pourrez supprimer le dossier `packages/` ainsi que les entrées
`"repositories"` correspondantes dans `composer.json`. Le lot est fourni
tel quel (avec `packages/`) pour fonctionner **immédiatement, sans aucune
étape supplémentaire de votre part**, quel que soit votre accès réseau.

## Fichiers du lot

- `composer.json`, `composer.lock` — remplacent les fichiers existants (ajout de dompdf et ses dépendances).
- `packages/` — dossier neuf contenant dompdf + dépendances (voir ci-dessus).
- `app/Services/PdfRenderer.php` — nouveau, wrapper dompdf centralisé.
- `app/Http/Controllers/ReceiptPdfController.php` — nouveau, téléchargement du reçu.
- `resources/views/pdf/receipt.blade.php`, `resources/views/pdf/report.blade.php` — nouvelles vues sources des PDF.
- `app/Filament/Resources/PaymentResource.php` — modifié (bouton "Télécharger le reçu").
- `app/Filament/Pages/Reports.php` — modifié (bouton "Exporter en PDF").
- `routes/web.php` — modifié (route de téléchargement du reçu).
- `tests/Feature/PdfExportsTest.php` — nouveau (4 tests).

## Étapes après copie

```bash
composer install    # installe dompdf depuis packages/ (aucun accès réseau nécessaire)
php artisan test --filter=PdfExportsTest   # 4 passed
php artisan test                            # suite complète, doit rester verte
```

## Ce qui a été vérifié

Les deux PDF ont été générés avec de vraies données (via `tinker`, sur une
vraie base MariaDB) et **visuellement inspectés** (conversion en image) :
mise en page correcte, tous les accents français s'affichent normalement
(dompdf embarque nativement la police DejaVu Sans, qui couvre l'alphabet
latin étendu), montants et dates correctement formatés.

## Ce qui a été ajouté

**Reçu de paiement PDF** : bouton "Télécharger le reçu" sur la fiche d'un
paiement payé, dans `PaymentResource`. Le PDF est généré à la volée à
chaque téléchargement (rien n'est stocké sur disque) et protégé par la même
policy que la consultation du paiement (`PaymentPolicy::view`) — un
établissement ne peut télécharger que les reçus de ses propres dossiers, un
compte régional que ceux de sa région, etc. Un utilisateur non authentifié
est redirigé vers la page de connexion du panel plutôt que de provoquer une
erreur.

**Rapport PDF** : bouton "Exporter en PDF" à côté du bouton CSV existant
sur la page Rapports & Statistiques, au format A4 paysage — mêmes données
que l'export CSV (compteurs de synthèse + tableau détaillé par
région/département/établissement selon le niveau de l'utilisateur), mises
en forme pour l'impression.

## Sécurité

`PdfRenderer` désactive explicitement le chargement de ressources distantes
(`isRemoteEnabled = false`) : aucun export PDF ne peut être détourné pour
déclencher une requête réseau sortante (SSRF) via une donnée saisie par un
utilisateur (nom d'établissement, motif de rejet, etc. affichés dans les
documents).
