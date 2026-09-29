<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicLevel extends Model
{
    protected $fillable = ['name', 'cycle', 'order'];

    public function classes(): HasMany
    {
        return $this->hasMany(Classe::class);
    }
}
