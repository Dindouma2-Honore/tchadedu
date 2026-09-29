<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Classe;
use App\Models\Guardian;
use App\Models\Student;
use Illuminate\Database\Seeder;

class StudentSeeder extends Seeder
{
    /**
     * Élèves fictifs répartis sur les classes déjà seedées, avec au moins
     * un tuteur rattaché à chacun (relation many-to-many student_guardian).
     */
    public function run(): void
    {
        $classes = Classe::query()->get();

        if ($classes->isEmpty()) {
            return;
        }

        foreach ($classes as $classe) {
            for ($i = 0; $i < 3; $i++) {
                $sexe = fake()->randomElement(['M', 'F']);

                $student = Student::query()->create([
                    'establishment_id' => $classe->establishment_id,
                    'class_id' => $classe->id,
                    'matricule' => null,
                    'last_name' => mb_strtoupper(fake()->lastName()),
                    'first_name' => $sexe === 'M' ? fake()->firstNameMale() : fake()->firstNameFemale(),
                    'birth_date' => fake()->dateTimeBetween('-19 years', '-14 years')->format('Y-m-d'),
                    'birth_place' => fake()->city(),
                    'sex' => $sexe,
                    'nationality' => 'Tchadienne',
                    'status' => 'actif',
                ]);

                $guardian = Guardian::query()->create([
                    'last_name' => mb_strtoupper(fake()->lastName()),
                    'first_name' => fake()->firstName(),
                    'phone' => '+235' . fake()->numerify('########'),
                    'email' => fake()->boolean(60) ? fake()->safeEmail() : null,
                    'profession' => fake()->boolean(70) ? fake()->jobTitle() : null,
                ]);

                $student->guardians()->attach($guardian->id, [
                    'relationship' => fake()->randomElement(['pere', 'mere', 'tuteur']),
                    'is_primary_contact' => true,
                ]);
            }
        }
    }
}
