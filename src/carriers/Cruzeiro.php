<?php

namespace shippingCalculator\carriers;

use shippingCalculator\abstractions\AbstractSsw;

class Cruzeiro extends AbstractSsw
{
    protected function setCompanyName(): void
    {
        $this->companyName = "Cruzeiro";
    }
}