<?php

namespace Minds\Helpers\StringLengthValidators;

/**
 * MessageLengthValidator - validates message field length.
 * e.g. an standard activity body.
 */
class MessageLengthValidator extends AbstractLengthValidator
{
    public function __construct()
    {
        parent::__construct(
            fieldName: 'message',
            min: 0,
            max: 20000
        );
    }
}
