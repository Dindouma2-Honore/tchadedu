<?php

declare(strict_types=1);

namespace App\Filament\Resources\EstablishmentResource\Pages;

use App\Filament\Resources\EstablishmentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEstablishments extends ListRecords
{
    protected static string $resource = EstablishmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
