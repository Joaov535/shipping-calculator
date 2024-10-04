<?php

namespace shippingCalculator\carriers;

use shippingCalculator\abstractions\AbstractSsw;

class Excellence extends AbstractSsw
{
    protected function setCompanyName(): void
    {
        $this->companyName = "Excellence";
    }
}