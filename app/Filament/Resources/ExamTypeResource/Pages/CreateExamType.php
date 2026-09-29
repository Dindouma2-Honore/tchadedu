<?php

declare(strict_types=1);

namespace App\Filament\Resources\ExamTypeResource\Pages;

use App\Filament\Resources\ExamTypeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateExamType extends CreateRecord
{
    protected static string $resource = ExamTypeResource::class;
}
