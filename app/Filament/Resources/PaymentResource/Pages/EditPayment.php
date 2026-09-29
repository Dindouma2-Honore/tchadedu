<?php

declare(strict_types=1);

namespace App\Filament\Resources\PaymentResource\Pages;

use App\Filament\Resources\PaymentResource;
use Filament\Resources\Pages\EditRecord;

class EditPayment extends EditRecord
{
    protected static string $resource = PaymentResource::class;

    protected function getHeaderActions(): array
    {
        return PaymentResource::workflowActions();
    }

    protected function getFormActions(): array
    {
        // Pas de bouton "Enregistrer" : le formulaire n'est composé que de
        // Placeholder en lecture seule (voir PaymentResource::form()).
        return [];
    }
}
