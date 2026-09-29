<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Payment> */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'reference' => 'PAY-'.now()->format('Y').'-'.Str::upper(Str::random(10)),
            'amount' => 5000,
            'currency' => 'XAF',
            'status' => Payment::STATUS_INITIATED,
            'provider' => 'simulated',
        ];
    }
}
