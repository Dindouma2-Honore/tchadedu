<?php

declare(strict_types=1);

namespace App\Filament\Resources\EstablishmentResource\Pages;

use App\Filament\Resources\EstablishmentResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditEstablishment extends EditRecord
{
    protected static string $resource = EstablishmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
