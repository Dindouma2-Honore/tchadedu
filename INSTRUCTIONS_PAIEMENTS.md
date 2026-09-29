# Paiements (avec intégration CinetPay) — Instructions d'application

## Principe central (section 18 du document)

> « Le système ne doit jamais considérer une simple redirection ou un clic
> comme preuve de paiement. Le statut doit être confirmé par le
> prestataire, idéalement par callback/webhook signé et vérification
> serveur à serveur. »

Toute l'architecture respecte cette règle strictement : **aucun code ne
change le statut d'un paiement sur la seule foi d'une notification** —
`CinetPayGateway::verify()` interroge systématiquement l'API officielle de
CinetPay pour obtenir le statut réel avant d'appliquer quoi que ce soit.

## Fichiers du lot (42 fichiers)

Nouveaux : contrats (`PaymentGateway`), modèles (`Payment`, `PaymentTransaction`,
`Receipt`), service central (`PaymentService`), passerelles
(`SimulatedPaymentGateway`, `CinetPayGateway`), contrôleurs (webhook
générique, webhook CinetPay, pages de démo/retour), policy, Resource
Filament, migrations, factory, tests (24 tests dédiés au module Paiements).

Modifiés : `Application` (contrôle de paiement avant soumission),
`ExamSession` (frais par session), `ApplicationWorkflowService` (blocage si
impayé), `ApplicationResource` (action "Payer les frais"),
`AppServiceProvider` (bascule automatique simulé/CinetPay),
`config/services.php`, `routes/web.php`, `bootstrap/app.php` (exclusion
CSRF des 2 webhooks), seeders de permissions/rôles.

## Étapes après copie

```bash
php artisan migrate
php artisan test --filter=PaymentsTest              # 14 passed
php artisan test --filter=CinetPayIntegrationTest   # 10 passed
php artisan test --filter=PaymentResourceSmokeTest  # 2 passed
php artisan test                                     # suite complète, doit rester verte
```

## Comment activer le vrai CinetPay (au lieu de la démo)

**Tant que ces 3 variables restent vides dans `.env`, l'application utilise
`SimulatedPaymentGateway`** (page de démo interne, aucun réseau réel) — vous
pouvez donc déployer et tester tout le reste sans compte CinetPay.

1. Créez un compte marchand gratuit sur https://app-new.cinetpay.com
2. Menu **« Intégrations »** → récupérez votre **APIKEY**, votre **SITE ID**
   et votre **Secret Key** (ces deux dernières sont différentes ; le Secret
   Key sert uniquement à vérifier la signature X-TOKEN des notifications).
3. Renseignez dans `.env` :
   ```
   CINETPAY_API_KEY=votre_apikey
   CINETPAY_SITE_ID=votre_site_id
   CINETPAY_SECRET_KEY=votre_secret_key
   ```
4. C'est tout. `AppServiceProvider` détecte automatiquement ces valeurs et
   bascule sur `CinetPayGateway` — **aucune autre modification de code**.
5. CinetPay fournit un compte marchand utilisable en test réel (pas un
   sandbox isolé séparé) : les premières transactions peuvent être
   effectuées avec de petits montants réels ou via leur interface de test,
   selon l'offre en vigueur sur votre compte — vérifiez avec leur support
   commercial les modalités actuelles.

Le webhook CinetPay à renseigner dans leur interface (si demandé
manuellement, en plus du `notify_url` déjà transmis automatiquement à
chaque paiement) :
```
https://votre-domaine.tc/webhooks/payments/cinetpay
```

## Ce qui a été vérifié (sans accès réseau réel à CinetPay depuis ce sandbox)

Le contenu exact de l'API CinetPay (endpoints, champs, formule de
signature X-TOKEN, valeurs de statut ACCEPTED/REFUSED/WAITING_FOR_CUSTOMER)
a été confirmé par recherche sur leur documentation officielle
(docs.cinetpay.com) avant d'écrire le code. Tous les appels HTTP sont
testés avec `Http::fake()` (simulation fidèle des réponses documentées),
ce qui valide la logique d'intégration, le parsing des réponses et le
mapping des statuts — **mais pas un appel réseau réel avec de vraies
clés**, cet environnement de travail n'ayant pas accès à internet en dehors
d'une liste blanche de domaines techniques (GitHub, npm, PyPI...). Le
premier vrai paiement de test, une fois vos clés en place, doit être vérifié
de votre côté.

## Point d'attention spécifique à WAITING_FOR_CUSTOMER

Certains opérateurs mobile money confirment le paiement par notification
push sur le téléphone du client, qui doit valider manuellement. CinetPay
envoie alors un statut intermédiaire `WAITING_FOR_CUSTOMER` — traité ici
comme **"en attente"**, jamais comme un échec (leur documentation prévient
explicitement que le traiter comme un échec définitif casse les paiements
de ces opérateurs). Le paiement reste donc `initiated`/`pending` jusqu'à un
prochain webhook confirmant réellement `ACCEPTED` ou `REFUSED`.

## Limitation connue

Aucun export PDF de reçu (juste un numéro de reçu enregistré) — même
limitation déjà documentée pour le module Rapports, pour la même raison
(pas d'accès Packagist depuis ce sandbox pour une lib PDF). Simple à
ajouter depuis votre machine avec la skill `pdf` déjà disponible.
