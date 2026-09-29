<?php

declare(strict_types=1);

namespace App\Filament\Resources\LocalityResource\Pages;

use App\Filament\Resources\LocalityResource;
use Filament\Resources\Pages\CreateRecord;

class CreateLocality extends CreateRecord
{
    protected static string $resource = LocalityResource::class;
}
