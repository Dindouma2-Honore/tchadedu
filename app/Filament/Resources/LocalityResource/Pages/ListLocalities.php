<?php

declare(strict_types=1);

namespace App\Filament\Resources\LocalityResource\Pages;

use App\Filament\Resources\LocalityResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLocalities extends ListRecords
{
    protected static string $resource = LocalityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
