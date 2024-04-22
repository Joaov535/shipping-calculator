<?php

namespace shippingCalculator\carriers;

use shippingCalculator\ShippingCostCalculator;

class Rodonaves
{
    private ShippingCostCalculator $shipping
    private \SplFixedArray $credentials;
    private \CurlHandle|false $curl;
    public const URL_TOKEN = "https://01wapi.rte.com.br/token";
    public const URL_QUOTE = "https://quotation-apigateway.rte.com.br/api/v1/simula-cotacao";
    private array $requestBody;

    /**
     * @param ShippingCostCalculator $shipping
     * @param array $credentials ["user" => xxxxxx, "password" => xxxxxx]
     */
    public function __construct(ShippingCostCalculator $shipping,array $credentials)
    {
        $this->shipping = $shipping;
        $this->credentials = new \SplFixedArray(2);
        $this->credentials['user'] = $credentials['user'];
        $this->credentials['password'] = $credentials['password'];
        $this->setBodyRequest();
    }

    private function getToken()
    {
        $data = 'auth_type=dev&grant_type=password&username=' . $this->credentials['user'] . '&password=' . $this->credentials['password'];
        $this->curl = curl_init(self::URL_TOKEN);
        curl_setopt_array(
            $this->curl,
            array(
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $data,
                CURLOPT_RETURNTRANSFER => true
            )
        );
        return json_decode(curl_exec($this->curl), true);

    }

    private function boxList()
    {
        $list = [];
        foreach ($this->shipping->getBoxList() as $box){
            $newBox = [
                "Weight" => $box->getWeight(),
                "Height" => $box->getHeight(),
                "Width" => $box->getWidth(),
                "Length" => $box->getLength(),
                "AmountPackages" => 1
            ];
            $list[] = $newBox;
        }
        return $list;
    }

    private function setBodyRequest(): void
    {
        $this->requestBody = [
            "OriginZipCode" => $this->shipping->getSenderZipCode(),
            "OriginCityId" => $this->shipping['CityId'],
            "DestinationZipCode" => $this->shipping->getReceiverZipCode(),
            "DestinationCityId" => $this->shipping['CityId'],
            "TotalWeight" => $this->shipping->getTotalWeight(),
            "EletronicInvoiceValue" => $this->shipping->getSerialValue(),
            "CustomerTaxIdRegistration" => $this->shipping->getSenderCNPJ(),
            "Packs" => $this->boxList()
        ];
    }

    public function doRequest(): array
    {

        $token = $this->getToken()['access_token'];
        $headers = ["Authorization: Bearer " . $token, "Content-Type: application/json"];
        curl_setopt_array(
            $this->curl,
            array(
                CURLOPT_URL => self::URL_QUOTE,
                CURLOPT_POST           => true,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_POSTFIELDS     => json_encode($this->requestBody),
                CURLOPT_RETURNTRANSFER => true
            )
        );

        $responseQuotation = json_decode(curl_exec($this->curl), true);
        curl_close($this->curl);

        $quotation = array();
        if (isset($responseQuotation['DeliveryTime']) && isset($responseQuotation['Value'])) {
            $referenceCode = 'code_quotation_rodonaves_' . time();
            $quotation['id'] = $referenceCode;
            $quotation['tempo_previsto'] = $responseQuotation['DeliveryTime'];
            $quotation['valor_total'] = $responseQuotation['Value'];
            $quotation['transportador'] = "Rodonaves";

        }
        return $quotation;
    }
}