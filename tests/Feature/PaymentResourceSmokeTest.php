<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentResourceSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_payments_list_renders_for_national_user(): void
    {
        $this->seed();
        $national = User::where('email', 'admin@tchadedu.com')->firstOrFail();

        $this->actingAs($national)->get('/admin/payments')->assertSuccessful();
    }

    public function test_payments_list_renders_for_establishment_user(): void
    {
        $this->seed();
        $etab = User::where('email', 'eboue@tchadedu.com')->firstOrFail();

        $this->actingAs($etab)->get('/admin/payments')->assertSuccessful();
    }
}
