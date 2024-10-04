<?php

namespace shippingCalculator\carriers;

use shippingCalculator\abstractions\AbstractCarriers;
use shippingCalculator\contracts\CarriersInterface;
use shippingCalculator\ShippingCostCalculator;

class Braspress extends AbstractCarriers
{
    public const API = "https://api.braspress.com/v1/cotacao/calcular/json";
    private string $requestBody;

    public function __construct(string $token, ShippingCostCalculator $shipping)
    {
        parent::__construct();
        $this->shipping = $shipping;
        $this->setCompanyName();
        $this->setCredentials($token);
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
        try {
            $this->response->transportador = $this->companyName;

            $ch = curl_init(self::API);

            curl_setopt_array(
                $ch, [
                    CURLOPT_POST => true,
                    CURLOPT_HTTPHEADER => [
                        'Authorization: Basic ' . $this->credentials,
                        'Content-Type: application/json',
                        'Content-Length: ' . strlen($this->requestBody)
                    ],
                    CURLOPT_POSTFIELDS => $this->requestBody,
                    CURLOPT_VERBOSE => false,
                    CURLOPT_RETURNTRANSFER => true

                ]
            );

            $response = json_decode(curl_exec($ch), true);

            if (curl_errno($ch)) throw new \Exception(curl_error($ch));

            curl_close($ch);

            if (isset($response['statusCode']) && $response['statusCode'] == 400) {
                throw new \Exception(($response['errorList'][0] ?? $response['message']) ?? "Sem Resposta");
            }

            if (empty($response['prazo'])) throw new \Exception("Prazo não retornado");
            if (empty($response['totalFrete'])) throw new \Exception("Valor do frete não retornado");

            $this->response->tempo_previsto = $response['prazo'];
            $this->response->valor_total = str_replace(",", ".", $response['totalFrete']);
        } catch (\Exception $e) {
            $this->response->exception = $e->getMessage();
        }
        return $this->response->toArray();
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

    protected function setCredentials(array|string $credentials): void
    {
        $this->credentials = $credentials;
    }

    protected function setCompanyName(): void
    {
        $this->companyName = "Braspress";
    }
}