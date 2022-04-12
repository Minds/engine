<?php

namespace Minds\Helpers\StringLengthValidators;

/**
 * DescriptionLengthValidator - validates description field length
 * e.g. channel description (also known as bio).
 */
class DescriptionLengthValidator extends AbstractLengthValidator
{
    public function __construct()
    {
        parent::__construct(
            fieldName: 'description',
            min: 0,
            max: 20000
        );
    }
}
