<?php

declare(strict_types=1);

namespace App\Services;

use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * Point d'entrée unique pour générer un PDF à partir de HTML (reçus de
 * paiement, rapports). Centralise les réglages de sécurité et de rendu
 * pour que chaque nouvel export PDF n'ait pas à les redéfinir.
 */
class PdfRenderer
{
    public function render(string $html, string $paper = 'a4', string $orientation = 'portrait'): string
    {
        $options = new Options;
        // Sécurité : ne jamais aller chercher une ressource (image, police...)
        // sur une URL externe depuis le HTML rendu — élimine tout risque de
        // SSRF via un export PDF construit à partir de données saisies.
        $options->set('isRemoteEnabled', false);
        // Police par défaut fournie avec dompdf, avec un jeu de caractères
        // complet (accents français, etc.) sans dépendre de polices système.
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper($paper, $orientation);
        $dompdf->render();

        return $dompdf->output();
    }
}
