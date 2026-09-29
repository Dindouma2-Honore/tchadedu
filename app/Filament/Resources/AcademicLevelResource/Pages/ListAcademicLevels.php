<?php

declare(strict_types=1);

namespace App\Filament\Resources\AcademicLevelResource\Pages;

use App\Filament\Resources\AcademicLevelResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAcademicLevels extends ListRecords
{
    protected static string $resource = AcademicLevelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
