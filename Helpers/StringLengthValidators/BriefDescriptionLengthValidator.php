<?php

namespace Minds\Helpers\StringLengthValidators;

/**
 * BriefDescriptionLengthValidator - validates briefdescription field length
 * e.g. the body of images and videos.
 */
class BriefDescriptionLengthValidator extends AbstractLengthValidator
{
    public function __construct()
    {
        parent::__construct(
            fieldName: 'briefdescription',
            min: 0,
            max: 5000
        );
    }
}
