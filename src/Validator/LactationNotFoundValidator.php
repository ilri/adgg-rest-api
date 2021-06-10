<?php

namespace App\Validator;

use App\Entity\AnimalEvent;
use Symfony\Component\Validator\{
    Constraint,
    ConstraintValidator,
};

class LactationNotFoundValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        /* @var $constraint LactationNotFound */

        // validate only animal events of type milking
        if (AnimalEvent::EVENT_TYPE_MILKING !== $value->getEventType()) {
            return;
        }

        // check if the animal event has calved already
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
