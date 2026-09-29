<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Student;
use App\Models\User;

class StudentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('students.view') || $user->hasPermissionTo('students.manage') || $user->scopeLevel() === 'self';
    }

    public function view(User $user, Student $student): bool
    {
        return $this->inScope($user, $student);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('students.manage');
    }

    public function update(User $user, Student $student): bool
    {
        return $this->inScope($user, $student) && $user->hasPermissionTo('students.manage');
    }

    public function delete(User $user, Student $student): bool
    {
        return $user->scopeLevel() === 'national';
    }

    private function inScope(User $user, Student $student): bool
    {
        if ($user->scopeLevel() === 'self') {
            return $student->id === $user->student_id;
        }

        $etablissement = $student->establishment;

        return match ($user->scopeLevel()) {
            'national' => true,
            'regional' => $etablissement?->region_id === $user->region_id,
            'departmental' => $etablissement?->department_id === $user->department_id,
            'establishment' => $student->establishment_id === $user->establishment_id,
            default => false,
        };
    }
}
