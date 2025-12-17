<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class NoInappropriateWords extends Constraint
{
    public string $message = 'Ce pseudo n\'est pas valide';
}