<?php

namespace App\Validator;

use Symfony\Component\Validator\{
    Constraint,
    ConstraintValidator,
};

class LactationNotFoundValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        /* @var $constraint LactationNotFound */

        if (null !== $value->getAnimal()->getLastCalving()) {
            return;
        }

        // TODO: implement the validation here
        $this->context->buildViolation($constraint->message)
            ->setParameter('{{ value }}', strval($value->getAnimal()->getId()))
            ->addViolation()
        ;
    }
}
