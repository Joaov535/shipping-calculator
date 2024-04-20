<?php

namespace shippingCalculator\carriers;

use shippingCalculator\ShippingCostCalculator;

class Braspress
{
    public const API = "https://api.braspress.com/v1/cotacao/calcular/json";
    private string $token;
    private $requestBody;
    private ShippingCostCalculator $shipping;

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
            'tipoFrete' => $this->shipping->getPaymentStatus(),
            'cepOrigem' => $this->shipping->getSenderZipCode(),
            'cepDestino' => $this->shipping->getReceiverZipCode(),
            'vlrMercadoria' => $this->shipping->getSerialValue(),
            'peso' => $this->shipping->getTotalWeight(),
            'volumes' => count($this->cubagem()),
            'cubagem' => $this->cubagem()
        ];

        $this->requestBody = json_encode($data);
    }

    public function doRequest(): array
    {
        $ch = curl_init(self::API);

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $this->requestBody);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Authorization: Basic ' . $this->token,
                'Content-Type: application/json',
                'Content-Length: ' . strlen($this->requestBody))
        );

        $response = json_decode(curl_exec($ch));

        if (curl_errno($ch)) {
            $response = [
                "erro" => curl_error($ch)
            ];
        }

        curl_close($ch);

        return $response;
    }

    private function cubagem(): array
    {
        $cubagem = [];
        foreach ($this->shipping->getBoxListInMeters() as $box) {
            $cubagem[] = [
                'altura' => $box['height'],
                'largura' => $box['width'],
                'comprimento' => $box['depth'],
                'volumes' => 1
            ];
        }

        return $cubagem;
    }

}