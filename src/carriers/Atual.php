<?php

namespace shippingCalculator\carriers;

use shippingCalculator\abstractions\AbstractSsw;

class Atual extends AbstractSsw
{
    protected function setCompanyName(): void
    {
        $this->companyName = "Atual Cargas";
    }
}