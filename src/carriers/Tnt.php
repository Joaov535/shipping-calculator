<?php

namespace shippingCalculator\carriers;

use shippingCalculator\ShippingCostCalculator;

class Tnt
{
    private ShippingCostCalculator $shipping;
    private \SplFixedArray $credentials;
    public const WSDL = "https://ws.tntbrasil.com.br/tntws/CalculoFrete?wsdl";
    private array $requestBody;

    /**
     * @param ShippingCostCalculator $shipping
     * @param array $credentials ["login" => "xxxxx@xxxx", "password" => "xxxxxx"]
     */
    public function __construct(ShippingCostCalculator $shipping, array $credentials)
    {
        $this->shipping = $shipping;
        $this->credentials = new \SplFixedArray();
        $this->credentials['login'] = $credentials['login'];
        $this->credentials['senha'] = $credentials['password'];
    }

    public function setRequestBody(): void
    {
        $this->requestBody = [
            "login" => $this->credentials['login'],
            "senha" => $this->credentials['senha'],
            "identificadorRemetente" => $this->shipping->getSenderCNPJ(),
            "identificadorDestinatario" => $this->shipping->getReceiverIdentification(),
            "tipoFrete" => "C", //CIF
            "tipoServico" => $this->shipping->getTransportType() == 'R' ? "RNC" : "ANC",
            "cepOrigem" => $this->shipping->getSenderZipCode(),
            "cepDestino" => $this->shipping->getReceiverZipCode(),
            "valorMercadoria" => $this->shipping->getSerialValue(),
            "peso" => $this->shipping->getTotalWeight(),
            "ieRemetente" => $this->shipping->getSenderIE(),
            "ieDestinatario" => $this->shipping->getReceiverIE(),
            "sitTributariaRemetente" => "CO",
            "sitTributariaDestinatario" => "CO",
            "divisaoCliente" => "1",
            "tipoPessoaRemetente" => $this->shipping->getSenderPersonType(),
            "tipoPessoaDestinatario" => $this->shipping->getReceiverPersonType()

        ];
    }

    public function doRequest(): array
    {
        try {
            $client = new \SoapClient(self::WSDL);
            $xml = $client->calculaFrete($this->requestBody);
            $tntQuotation = (array)simplexml_load_string($xml);

            $quotation = [];
            $quotation['tempo_previsto'] = $tntQuotation['prazoEntrega'];
            $quotation['valor_total'] = $tntQuotation['vlTotalFrete'];
            return $quotation;
            } catch (\Exception $e) {
                return [
                    "erro" => "TNT: " . $e->getMessage()
                ];
        }
    }
}