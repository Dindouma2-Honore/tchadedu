<?php

declare(strict_types=1);

namespace App\Filament\Resources\GuardianResource\Pages;

use App\Filament\Resources\GuardianResource;
use Filament\Resources\Pages\CreateRecord;

class CreateGuardian extends CreateRecord
{
    protected static string $resource = GuardianResource::class;
}
