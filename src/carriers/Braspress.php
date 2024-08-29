<?php

namespace shippingCalculator\carriers;

use shippingCalculator\ShippingCostCalculator;

class Braspress implements CarriersInterface
{
    public const API = "https://api.braspress.com/v1/cotacao/calcular/json";
    private string $token;
    private string $requestBody;
    private ShippingCostCalculator $shipping;
    private string $companyName = 'Braspress';

    public function __construct(string $token, ShippingCostCalculator $shipping)
    {
        $this->token = $token;
        $this->shipping = $shipping;
        $this->setRequestBody();
    }

    private function setRequestBody(): void
    {
        $data = [
            'cnpjRemetente' => $this->shipping->getSenderCNPJ(),
            'cnpjDestinatario' => $this->shipping->getReceiverIdentification(),
            'modal' => $this->shipping->getTransportType(),
            'tipoFrete' => "1",
            'cepOrigem' => $this->shipping->getSenderZipCode(),
            'cepDestino' => $this->shipping->getReceiverZipCode(),
            'vlrMercadoria' => $this->shipping->getSerialValue(),
            'peso' => $this->shipping->getTotalWeight(),
            'volumes' => $this->shipping->getNumTotalBoxes(),
            'cubagem' => $this->cubagem()
        ];

        $this->requestBody = json_encode($data);
    }

    public function doRequest(): array
    {
        $ch = curl_init(self::API);

        curl_setopt_array(
            $ch, [
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Basic ' . $this->token,
                    'Content-Type: application/json',
                    'Content-Length: ' . strlen($this->requestBody)
                ],
                CURLOPT_POSTFIELDS => $this->requestBody,
                CURLOPT_VERBOSE => true,
                CURLOPT_RETURNTRANSFER => true

            ]
        );

        $response = json_decode(curl_exec($ch), true);

        if (curl_errno($ch)) {
            $response = [
                "erro" => curl_error($ch)
            ];
        }

        curl_close($ch);

        if (is_null($response)) {
            $quotation['id'] = $this->companyName . '_' . time();
            $quotation['transportador'] = $this->companyName;
            $quotation['tempo_previsto'] = "Sem resposta";
            $quotation['valor_total'] = 0;
            return $quotation;
        }

        if($response['statusCode'] == 400) {
            $quotation['id'] = $this->companyName . '_' . time();
            $quotation['transportador'] = $this->companyName;
            $quotation['tempo_previsto'] = $response['errorList'][0] ?? $response['message'];
            $quotation['valor_total'] = 0;
            return $quotation;
        }

        $quotation['id'] = $response['id'];
        $quotation['transportador'] = $this->companyName;
        $quotation['tempo_previsto'] = $response['prazo'] ?? "Sem retorno do prazo";
        $quotation['valor_total'] = str_replace(",", ".", $response['totalFrete']);

        return $quotation;
    }

    private function cubagem(): array
    {
        $cubagem = [];
        foreach ($this->shipping->getBoxListInMeters() as $box) {
            $cubagem[] = [
                'altura' => $box['height'],
                'largura' => $box['width'],
                'comprimento' => $box['depth'],
                'volumes' => $box['numBoxes']
            ];
        }

        return $cubagem;
    }

}