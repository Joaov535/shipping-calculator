<?php

namespace shippingCalculator\carriers;

use shippingCalculator\ShippingCostCalculator;

class AlfaTransportes
{
    public const API = "http://api.alfatransportes.com.br/cotacao/";
    private string $urlRequest;

    public function __construct(ShippingCostCalculator $shipping, $token)
    {
        $this->urlRequest = self::API .
            $token .
            $this->isCorporation($shipping->getSenderPersonType()) .
            $shipping->getSenderZipCode() .
            $shipping->getReceiverZipCode() .
            $shipping->getReceiverIdentification() .
            $shipping->getSerialValue() .
            $shipping->getTotalVolume() .
            1;
    }

    private function isCorporation($senderType)
    {
        return $senderType == 'J' ? 1 : 0;
    }

    public function doRequest(): array
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->urlRequest);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = json_decode(curl_exec($ch), true);
        curl_close($ch);

        if (curl_errno($ch)) {
            $response = [
                "erro" => curl_error($ch)
            ];
        }

        return $response;
    }
}