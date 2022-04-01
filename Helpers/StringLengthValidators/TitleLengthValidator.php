<?php

namespace Minds\Helpers\StringLengthValidators;

/**
 * TitleLengthValidator - validates title field length.
 * e.g. the title of an image or video.
 */
class TitleLengthValidator extends AbstractLengthValidator
{
    public function __construct()
    {
        parent::__construct(
            fieldName: 'title',
            min: 0,
            max: 2000
        );
    }
}
