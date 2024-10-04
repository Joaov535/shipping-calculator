<?php

namespace shippingCalculator\carriers;

use shippingCalculator\abstractions\AbstractCarriers;
use shippingCalculator\ShippingCostCalculator;

class Rodonaves extends AbstractCarriers
{
    public const URL_QUOTATION_TOKEN = "https://quotation-apigateway.rte.com.br/token";
    public const URL_CITY_ID_TOKEN = "https://01wapi.rte.com.br/token";
    public const URL_QUOTE = "https://quotation-apigateway.rte.com.br/api/v1/simula-cotacao";
    private array $requestBody;

    public function __construct(ShippingCostCalculator $shipping, array $credentials)
    {
        parent::__construct();
        $this->shipping = $shipping;
        $this->setCompanyName();
        $this->setCredentials($credentials);
        $this->setBodyRequest();
    }

    protected function setCredentials(array|string $credentials): void
    {
        $this->credentials['user'] = $credentials['user'];
        $this->credentials['password'] = $credentials['password'];
    }

    protected function setCompanyName(): void
    {
        $this->companyName = 'Rodonaves';
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
        try {
            $this->response->transportador = $this->companyName;

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
                curl_close($ch);
                throw new \Exception(curl_error($ch));
            }

            $responseQuotation = json_decode($response, true);
            curl_close($ch);

            if (empty($responseQuotation['DeliveryTime']))  throw new \Exception("Tempo previsto não retornado");
            $this->response->tempo_previsto = $responseQuotation['DeliveryTime'];

            if (empty($responseQuotation['Value'])) throw new \Exception("Valor do frete não retornado");
            $this->response->valor_total = $responseQuotation['Value'];
        } catch (\Exception $e) {
            $this->response->exception = $e->getMessage();
        }

        return $this->response->toArray();
    }
}