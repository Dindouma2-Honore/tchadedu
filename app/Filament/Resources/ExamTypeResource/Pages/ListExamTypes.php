<?php

declare(strict_types=1);

namespace App\Filament\Resources\ExamTypeResource\Pages;

use App\Filament\Resources\ExamTypeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListExamTypes extends ListRecords
{
    protected static string $resource = ExamTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
