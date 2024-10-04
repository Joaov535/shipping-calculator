<?php

namespace shippingCalculator\carriers;

use shippingCalculator\abstractions\AbstractSsw;

class BauerExpress extends AbstractSsw
{
    protected int $commodity = 3;

    protected function setCompanyName(): void
    {
        $this->companyName = "Bauer Express";
    }
}