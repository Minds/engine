<?php

namespace Minds\Helpers\StringLengthValidators;

/**
 * UsernameLengthValidator - validates username field length.
 */
class UsernameLengthValidator extends AbstractLengthValidator
{
    public function __construct()
    {
        parent::__construct(
            fieldName: 'username',
            min: 1,
            max: 50
        );
    }
}
