<?php

namespace shippingCalculator\carriers;

use shippingCalculator\ShippingCostCalculator;

class Rodonaves implements CarriersInterface
{
    private ShippingCostCalculator $shipping;
    private array $credentials;
    public const URL_QUOTATION_TOKEN = "https://quotation-apigateway.rte.com.br/token";
    public const URL_CITY_ID_TOKEN = "https://01wapi.rte.com.br/token";
    public const URL_QUOTE = "https://quotation-apigateway.rte.com.br/api/v1/simula-cotacao";
    private array $requestBody;

    public function __construct(ShippingCostCalculator $shipping, array $credentials)
    {
        $this->shipping = $shipping;
        $this->credentials['user'] = $credentials['user'];
        $this->credentials['password'] = $credentials['password'];
        $this->setBodyRequest();
    }

    private function getToken(string $url): string
    {
        $data = http_build_query([
            'auth_type' => 'DEV',
            'grant_type' => 'password',
            'username' => $this->credentials['user'],
            'password' => $this->credentials['password']
        ]);

        $headers = [
            'content-type' => 'application/x-www-form-urlencoded'
        ];

        $ch = curl_init($url);
        curl_setopt_array(
            $ch,
            array(
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $data,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => $headers
            )
        );
        $res = json_decode(curl_exec($ch), true);

        return $res['access_token'];
    }

    private function getCityCode($cep): array
    {
        $url = "https://01wapi.rte.com.br/api/v1/busca-por-cep?zipCode={$cep}";
        $ch = curl_init($url);

        $headers = [
            "Authorization: Bearer {$this->getToken(self::URL_CITY_ID_TOKEN)}",
            'accept: application/json',
            'content-type: application/*+json'
        ];
        curl_setopt_array(
            $ch,
            array(
                CURLOPT_URL => $url,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_POST => false,
                CURLOPT_RETURNTRANSFER => true
            )
        );

        $res = json_decode(curl_exec($ch), true);

        if (!isset($res['CityId'])) $res['CityId'] = null;

        return $res;
    }

    private function getBoxList(): array
    {
        $list = [];
        foreach ($this->shipping->getBoxList() as $box) {
            $newBox = [
                "Weight" => round(($this->shipping->getTotalWeight() / $this->shipping->getNumTotalBoxes()), 2),
                "Height" => $box['height'],
                "Width" => $box['width'],
                "Length" => $box['depth'],
                "AmountPackages" => intval($box['numBoxes'])
            ];
            $list[] = $newBox;
        }
        return $list;
    }

    private function setBodyRequest(): void
    {
        $this->requestBody = [
            "OriginZipCode" => $this->shipping->getSenderZipCode(),
            "OriginCityId" => $this->getCityCode($this->shipping->getSenderZipCode())['CityId'],
            "DestinationZipCode" => $this->shipping->getReceiverZipCode(),
            "DestinationCityId" => $this->getCityCode($this->shipping->getReceiverZipCode())['CityId'],
            "TotalWeight" => $this->shipping->getTotalWeight(),
            "EletronicInvoiceValue" => $this->shipping->getSerialValue(),
            "CustomerTaxIdRegistration" => $this->shipping->getSenderCNPJ(),
            "TotalPackages" => $this->shipping->getNumTotalBoxes(),
            "Packs" => $this->getBoxList()
        ];
    }

    public function doRequest(): array
    {
        $ch = curl_init(self::URL_QUOTE);
        $headers = [
            "Authorization: Bearer {$this->getToken(self::URL_QUOTATION_TOKEN)}",
            'accept: application/json',
            'content-type: application/*+json'
        ];

        curl_setopt_array(
            $ch,
            array(
                CURLOPT_URL => self::URL_QUOTE,
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_POSTFIELDS => json_encode($this->requestBody),
                CURLOPT_RETURNTRANSFER => true
            )
        );

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            echo 'Erro: ' . curl_error($ch);
            curl_close($ch);
            return [];
        }

        $responseQuotation = json_decode($response, true);
        curl_close($ch);

        $quotation['id'] = 'Rodonaves_' . time();
        $quotation['tempo_previsto'] = isset( $responseQuotation['DeliveryTime']) ? $responseQuotation['DeliveryTime'] : "Sem resposta";
        $quotation['valor_total'] = $responseQuotation['Value'] ?: 0;
        $quotation['transportador'] = "Rodonaves";

        return $quotation;
    }

}