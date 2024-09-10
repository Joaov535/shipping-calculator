<?php

namespace shippingCalculator\carriers;

use shippingCalculator\carriers\AbstractSsw;

class BauerExpress extends AbstractSsw
{
    protected int $commodity = 3;
}