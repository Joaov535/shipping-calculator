<?php
/**
 * @author João V. Cruz
 * @date 04/10/2024
 */

namespace shippingCalculator\abstractions;

use shippingCalculator\contracts\CarriersInterface;
use shippingCalculator\models\Response;
use shippingCalculator\ShippingCostCalculator;

abstract class AbstractCarriers implements CarriersInterface
{
    protected string $companyName;
    protected ShippingCostCalculator $shipping;
    protected array|string $credentials;
    protected Response $response;

    public function __construct()
    {
        $this->response = new Response();
    }

    abstract protected function setCredentials(array|string $credentials);
    abstract protected function setCompanyName();
}