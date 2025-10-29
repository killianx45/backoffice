<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class ValidEmailAddress extends Constraint
{
    public string $message = 'email.validation.invalid';
    public string $invalidDomainMessage = 'email.validation.invalid_domain';
    public string $disposableEmailMessage = 'email.validation.disposable';
}
