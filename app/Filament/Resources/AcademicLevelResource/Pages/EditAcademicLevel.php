<?php

declare(strict_types=1);

namespace App\Filament\Resources\AcademicLevelResource\Pages;

use App\Filament\Resources\AcademicLevelResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAcademicLevel extends EditRecord
{
    protected static string $resource = AcademicLevelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
