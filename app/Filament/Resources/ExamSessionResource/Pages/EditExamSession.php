<?php

declare(strict_types=1);

namespace App\Filament\Resources\ExamSessionResource\Pages;

use App\Filament\Resources\ExamSessionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditExamSession extends EditRecord
{
    protected static string $resource = ExamSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
