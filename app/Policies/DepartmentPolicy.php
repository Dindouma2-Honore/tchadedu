<?php

declare(strict_types=1);

namespace App\Policies;

use App\Policies\Concerns\RestrictsWritesToNational;

class DepartmentPolicy
{
    use RestrictsWritesToNational;
}
