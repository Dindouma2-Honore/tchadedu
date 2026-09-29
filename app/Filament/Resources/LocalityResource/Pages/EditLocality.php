<?php

declare(strict_types=1);

namespace App\Filament\Resources\LocalityResource\Pages;

use App\Filament\Resources\LocalityResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditLocality extends EditRecord
{
    protected static string $resource = LocalityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
