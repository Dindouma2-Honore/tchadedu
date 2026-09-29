<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Region extends Model
{
    protected $fillable = ['code', 'name'];

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }

    public function establishments(): HasMany
    {
        return $this->hasMany(Establishment::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
