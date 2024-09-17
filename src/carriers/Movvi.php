<?php

namespace shippingCalculator\carriers;

use shippingCalculator\contracts\CarriersInterface;
use shippingCalculator\ShippingCostCalculator;

class Movvi implements CarriersInterface
{

    private string $token;
    private ShippingCostCalculator $shipping;
    private array $credentials;
    private string $companyName = "Movvi";
    public const URL_TOKEN = "https://sim.movvi.com.br/publico/login_check";
    public const URL_QUOTE = "https://sim.movvi.com.br/publico/api/precificacao/";
    private array $requestBody;

    public function __construct(ShippingCostCalculator $shipping, array $credentials)
    {
        $this->shipping = $shipping;
        $this->credentials['username'] = $credentials['user'];
        $this->credentials['password'] = $credentials['password'];
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

    private function setBodyRequest()
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

    public function doRequest()
    {
        $urlCotacao = "https://sim.movvi.com.br/publico/api/precificacao/";
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

        if (!isset($responseQuotation['mensagem'])) {
            $quotation['id'] = $this->companyName .'_'.time();
            $quotation['tempo_previsto'] = $responseQuotation['cotacoes']['horasPrazoEntrega'] / 24;
            $quotation['valor_total'] = round(floatval($responseQuotation['cotacoes']['ffValorCotacao']), 2);
            $quotation['transportador'] = $this->companyName;

            return $quotation;
        }

        $quotation['id'] =  $this->companyName .'_'.time();
        $quotation['transportador'] = $this->companyName;
        $quotation['tempo_previsto'] = "Sem resposta";
        $quotation['valor_total'] = 0;
        return $quotation;
    }

}