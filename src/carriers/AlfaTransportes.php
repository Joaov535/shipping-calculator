<?php

namespace shippingCalculator\carriers;

use shippingCalculator\abstractions\AbstractCarriers;
use shippingCalculator\ShippingCostCalculator;

class AlfaTransportes extends AbstractCarriers
{
    public const API = "https://api.alfatransportes.com.br/cotacao/?";
    private string $urlRequest;

    public function __construct(ShippingCostCalculator $shipping, $token)
    {
        parent::__construct();
        $this->credentials = $token;
        $this->shipping = $shipping;
        $this->setCompanyName();
        $this->setRequestUrl();
    }

    protected function setCredentials(array|string $credentials): void
    {
        $this->credentials = $credentials;
    }

    protected function setCompanyName(): void
    {
        $this->companyName = "Alfa Transportes";
    }

    private function isCorporation($senderType)
    {
        return $senderType == 'J' ? 1 : 0;
    }

    private function setRequestUrl(): void
    {
        $this->urlRequest = self::API .
            "idr=" . $this->credentials .
            "&cliTip=" . $this->isCorporation($this->shipping->getSenderPersonType()) .
            "&cepRem=" . $this->shipping->getSenderZipCode() .
            "&cliCep=" . $this->shipping->getReceiverZipCode() .
            "&cliCnpj=" . $this->shipping->getReceiverIdentification() .
            "&merVlr=" . $this->shipping->getSerialValue() .
            "&merPeso=" . $this->shipping->getTotalWeight() .
            "&merM3=" . $this->shipping->getTotalVolumeInMeters() .
            "&modoJson=" . "1";
    }

    public function doRequest(): array
    {
        try {
            $this->response->transportador = $this->companyName;

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->urlRequest);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_ENCODING, 'UTF-8');
            $response = json_decode(curl_exec($ch), true);
            curl_close($ch);

            if (empty($response['cotacao'])) {
                throw new \Exception("Sem resposta");
            }

            $this->response->tempo_previsto = intval($response['cotacao']['emissao']['diasEntrega']);
            $this->response->valor_total = $response['cotacao']['emissao']['valoresCotacao']['valorTotal'];
        } catch (\Exception $e) {
            $this->response->exception = $e->getMessage();
        }

        return $this->response->toArray();
    }


}