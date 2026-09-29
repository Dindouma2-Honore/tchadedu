<?php

declare(strict_types=1);

namespace App\Filament\Resources\GuardianResource\Pages;

use App\Filament\Resources\GuardianResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditGuardian extends EditRecord
{
    protected static string $resource = GuardianResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
