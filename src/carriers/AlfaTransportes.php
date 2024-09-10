<?php

namespace shippingCalculator\carriers;

use shippingCalculator\ShippingCostCalculator;

class AlfaTransportes implements CarriersInterface
{
    public const API = "https://api.alfatransportes.com.br/cotacao/?";
    private string $urlRequest;

    public function __construct(ShippingCostCalculator $shipping, $token)
    {
        $this->urlRequest = self::API .
            "idr=" . $token .
            "&cliTip=" . $this->isCorporation($shipping->getSenderPersonType()) .
            "&cepRem=" . $shipping->getSenderZipCode() .
            "&cliCep=" . $shipping->getReceiverZipCode() .
            "&cliCnpj=" . $shipping->getReceiverIdentification() .
            "&merVlr=" . $shipping->getSerialValue() .
            "&merPeso=" . $shipping->getTotalWeight() .
            "&merM3=" . $shipping->getTotalVolumeInMeters() .
            "&modoJson=" . "1";
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

        $quotation = [];
        $quotation['id'] = 'Alfa_Transportes_' . time();
        $quotation['transportador'] = 'Alfa Transportes';

        if (empty($response['cotacao'])) {
            $quotation['tempo_previsto'] =  'Sem resposta';
            $quotation['valor_total'] = 0;
        } else {
            $quotation['tempo_previsto'] =  intval($response['cotacao']['emissao']['diasEntrega']);
            $quotation['valor_total'] = $response['cotacao']['emissao']['valoresCotacao']['valorTotal'];
        }

        if (curl_errno($ch)) {
            $quotation['tempo_previsto'] =  'Sem resposta';
            $quotation['valor_total'] = 0;
        }

        return $quotation;
    }
}