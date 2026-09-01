<?php

use App\Classes\RefundPolicy;

function getRefundPolicy() : array
{
    return RefundPolicy::getTerms(RefundPolicy::AR | RefundPolicy::EN);
}

