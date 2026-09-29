<?php

declare(strict_types=1);

namespace App\Filament\Resources\ExamSessionResource\Pages;

use App\Filament\Resources\ExamSessionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateExamSession extends CreateRecord
{
    protected static string $resource = ExamSessionResource::class;
}
