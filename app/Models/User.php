<?php

declare(strict_types=1);

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory;
    use Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'is_active',
        'region_id',
        'department_id',
        'establishment_id',
        'student_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function establishment(): BelongsTo
    {
        return $this->belongsTo(Establishment::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user');
    }

    /**
     * Sans cette méthode, Filament n'autorise l'accès au panel qu'en
     * environnement local (voir Filament\Http\Middleware\Authenticate) :
     * indispensable pour que l'authentification fonctionne réellement en
     * recette/production, et pas seulement en développement local.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_active;
    }

    /** Vérifie si l'utilisateur possède la permission (via l'un de ses rôles). */
    public function hasPermissionTo(string $permissionSlug): bool
    {
        return $this->roles
            ->loadMissing('permissions')
            ->flatMap(fn (Role $role) => $role->permissions)
            ->contains('slug', $permissionSlug);
    }

    /**
     * Renvoie le niveau hiérarchique déduit des colonnes de périmètre
     * renseignées (section 31.1 du document d'architecture).
     */
    public function scopeLevel(): string
    {
        return match (true) {
            $this->student_id !== null => 'self',
            $this->establishment_id !== null => 'establishment',
            $this->department_id !== null => 'departmental',
            $this->region_id !== null => 'regional',
            default => 'national',
        };
    }
}
