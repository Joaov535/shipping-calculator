<?php

namespace shippingCalculator\carriers;

use shippingCalculator\abstractions\AbstractCarriers;
use shippingCalculator\ShippingCostCalculator;

class Movvi extends AbstractCarriers
{
    private const URL_TOKEN = "https://sim.movvi.com.br/publico/login_check";
    private const URL_QUOTE = "https://sim.movvi.com.br/publico/api/precificacao/";
    private string $token;
    private array $requestBody;

    public function __construct(ShippingCostCalculator $shipping, array $credentials)
    {
        parent::__construct();
        $this->shipping = $shipping;
        $this->setCompanyName();
        $this->setCredentials($credentials);
        $this->setToken();
        $this->setBodyRequest();
    }

    private function setToken(): void
    {
        $headers = ['Content-Type: application/json'];
        $ch = curl_init(self::URL_TOKEN);
        curl_setopt_array(
            $ch,
            array(
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_POSTFIELDS => json_encode($this->credentials),
                CURLOPT_VERBOSE => false,
                CURLOPT_RETURNTRANSFER => true
            )
        );
        $responseToken = json_decode(curl_exec($ch), true);
        $token = $responseToken['token'];
        curl_close($ch);

        $this->token = $token;
    }

    private function getBoxList(): array
    {
        $boxList = [];
        foreach ($this->shipping->getBoxList() as $box) {
            $newBox = array(
                "altura" => $box['height'],
                "largura" => $box['width'],
                "comprimento" => $box['depth'],
                "quantidade" => $box['numBoxes'],
            );
            $boxList[] = $newBox;
        }
        return $boxList;
    }

    private function setBodyRequest(): void
    {
        $this->requestBody = [
            "dadosCotacao" => [
                "pesoMercadoria" => $this->shipping->getTotalWeight(),
                "valorMercadoria" => $this->shipping->getSerialValue(),
                "destinatario" => $this->shipping->getReceiverIdentification(),
                "tipoMercadoria" => "VOLUME",
                "cepDestino" => $this->shipping->getReceiverZipCode(),
                "medidas" => $this->getBoxList()
            ]
        ];
    }

    public function doRequest(): array
    {
        try {
            $this->response->transportador = $this->companyName;

            $requestHeaderCotacao = array(
                "Authorization: Bearer " . $this->token,
                "Content-Type: application/json",
            );

            $chCotacao = curl_init(self::URL_QUOTE);
            curl_setopt_array(
                $chCotacao,
                array(
                    CURLOPT_POST => true,
                    CURLOPT_HTTPHEADER => $requestHeaderCotacao,
                    CURLOPT_POSTFIELDS => json_encode($this->requestBody),
                    CURLOPT_VERBOSE => false,
                    CURLOPT_RETURNTRANSFER => true
                )
            );

            $responseQuotation = json_decode(curl_exec($chCotacao), true);
            curl_close($chCotacao);

            if (empty($responseQuotation['cotacoes']['horasPrazoEntrega'])) throw new \Exception("Prazo não retornado");
            if (empty($responseQuotation['cotacoes']['ffValorCotacao'])) throw new \Exception("Valor do frete não retornado");

            $this->response->tempo_previsto = $responseQuotation['cotacoes']['horasPrazoEntrega'] / 24;
            $this->response->valor_total = round(floatval($responseQuotation['cotacoes']['ffValorCotacao']), 2);

        } catch (\Exception $e) {
            $this->response->exception = $e->getMessage();
        }

        return $this->response->toArray();
    }

    protected function setCredentials(array|string $credentials)
    {
        $this->credentials['username'] = $credentials['user'];
        $this->credentials['password'] = $credentials['password'];
    }

    protected function setCompanyName(): void
    {
        $this->companyName = "Movvi";
    }
}