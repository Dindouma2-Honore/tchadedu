<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('payments.view');
    }

    public function view(User $user, Payment $payment): bool
    {
        return $user->hasPermissionTo('payments.view') && $this->inScope($user, $payment);
    }

    public function initiate(User $user, Payment $payment): bool
    {
        return $user->hasPermissionTo('payments.initiate') && $this->ownsEstablishment($user, $payment);
    }

    public function manualConfirm(User $user, Payment $payment): bool
    {
        return $user->hasPermissionTo('payments.manual_confirm') && ! $payment->isTerminal();
    }

    public function refund(User $user, Payment $payment): bool
    {
        return $user->hasPermissionTo('payments.refund') && $payment->status === Payment::STATUS_SUCCESS;
    }

    private function ownsEstablishment(User $user, Payment $payment): bool
    {
        if ($user->scopeLevel() === 'national') {
            return true;
        }

        return $user->scopeLevel() === 'establishment' && $payment->application->establishment_id === $user->establishment_id;
    }

    private function inScope(User $user, Payment $payment): bool
    {
        $etablissement = $payment->application->establishment;

        return match ($user->scopeLevel()) {
            'national' => true,
            'regional' => $etablissement?->region_id === $user->region_id,
            'departmental' => $etablissement?->department_id === $user->department_id,
            'establishment' => $payment->application->establishment_id === $user->establishment_id,
            'self' => $payment->application->student_id === $user->student_id,
            default => false,
        };
    }
}
