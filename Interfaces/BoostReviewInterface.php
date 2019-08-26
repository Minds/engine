<?php

namespace Minds\Interfaces;

interface BoostReviewInterface
{
    public function setBoost($boost);

    public function accept();

    public function reject($reason);

    public function revoke();
}
