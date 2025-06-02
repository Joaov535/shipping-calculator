<?php

namespace shippingCalculator\carriers;

use shippingCalculator\abstractions\AbstractCarriers;
use shippingCalculator\ShippingCostCalculator;

class Tnt extends AbstractCarriers
{
    private const WSDL = "https://ws.tntbrasil.com.br/tntws/CalculoFrete?wsdl";
    private array $requestBody;
    private string $xmlr;

    /**
     * @param ShippingCostCalculator $shipping
     * @param array $credentials ["login" => "xxxxx@xxxx", "password" => "xxxxxx"]
     */
    public function __construct(ShippingCostCalculator $shipping, array $credentials)
    {
        parent::__construct();
        $this->shipping = $shipping;
        $this->setCompanyName();
        $this->setCredentials($credentials);
        $this->setRequestBody();
        $this->setXmlr();
    }

    protected function setCredentials(array|string $credentials): void
    {
        $this->credentials['login'] = $credentials['login'];
        $this->credentials['senha'] = $credentials['password'];
    }

    protected function setCompanyName()
    {
        $this->companyName = 'TNT';
    }

    public function setRequestBody(): void
    {
        $this->requestBody = [
            "login" => $this->credentials['login'],
            "senha" => $this->credentials['senha'],
            "identificadorRemetente" => $this->shipping->getSenderCNPJ(),
            "identificadorDestinatario" => $this->shipping->getReceiverIdentification(),
            "tipoFrete" => "C", //CIF
            "tipoServico" => $this->shipping->getTransportType() == 'A' ? "ANC" : "RNC",
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

    private function setXmlr(): void
    {
        $this->xmlr = "<soapenv:Envelope xmlns:soapenv='http://schemas.xmlsoap.org/soap/envelope/' xmlns:ser='http://service.calculoFrete.mercurio.com' xmlns:mod='http://model.vendas.lms.mercurio.com'>
                    <soapenv:Header/>
                    <soapenv:Body>
                        <ser:calculaFrete>
                            <ser:in0>
                                <mod:login>{$this->requestBody['login']}</mod:login>
                                <mod:senha>{$this->requestBody['senha']}</mod:senha>
                                <mod:nrIdentifClienteRem>{$this->requestBody['identificadorRemetente']}</mod:nrIdentifClienteRem>
                                <mod:nrIdentifClienteDest>{$this->requestBody['identificadorDestinatario']}</mod:nrIdentifClienteDest>
                                <mod:tpFrete>{$this->requestBody['tipoFrete']}</mod:tpFrete>
                                <mod:tpServico>{$this->requestBody['tipoServico']}</mod:tpServico>
                                <mod:cepOrigem>{$this->requestBody['cepOrigem']}</mod:cepOrigem>
                                <mod:cepDestino>{$this->requestBody['cepDestino']}</mod:cepDestino>
                                <mod:vlMercadoria>{$this->requestBody['valorMercadoria']}</mod:vlMercadoria>
                                <mod:psReal>{$this->requestBody['peso']}</mod:psReal>
                                <mod:nrInscricaoEstadualRemetente>{$this->requestBody['ieRemetente']}</mod:nrInscricaoEstadualRemetente>
                                <mod:nrInscricaoEstadualDestinatario>{$this->requestBody['ieDestinatario']}</mod:nrInscricaoEstadualDestinatario>
                                <mod:tpSituacaoTributariaRemetente>{$this->requestBody['sitTributariaRemetente']}</mod:tpSituacaoTributariaRemetente>
                                <mod:tpSituacaoTributariaDestinatario>{$this->requestBody['sitTributariaDestinatario']}</mod:tpSituacaoTributariaDestinatario>
                                <mod:cdDivisaoCliente>{$this->requestBody['divisaoCliente']}</mod:cdDivisaoCliente>
                                <mod:tpPessoaRemetente>{$this->requestBody['tipoPessoaRemetente']}</mod:tpPessoaRemetente>
                                <mod:tpPessoaDestinatario>{$this->requestBody['tipoPessoaDestinatario']}</mod:tpPessoaDestinatario>
                            </ser:in0>
                        </ser:calculaFrete>
                    </soapenv:Body>
                    </soapenv:Envelope>";
    }

    public function doRequest(): array
    {
        $this->response->transportador = $this->companyName;

        try {
            $action_URL = self::WSDL;

            $headers = [
                'Content-Type: text/xml; charset=utf-8',
                'SOAPAction: ""',
                'Content-Length: ' . strlen($this->xmlr)
            ];

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $action_URL,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $this->xmlr,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_USERAGENT => 'PHP cURL SOAP Client',
                CURLOPT_VERBOSE => true
            ]);

            $xml = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) {
                throw new \Exception("cURL Error: " . $error);
            }

            if ($httpCode !== 200) {
                throw new \Exception("HTTP Error: " . $httpCode);
            }

            if (!empty($xml)) {
                $dom = new \DOMDocument('1.0', 'UTF-8');
                libxml_use_internal_errors(true);
                $loaded = $dom->loadXml($xml);

                if (!$loaded) {
                    libxml_clear_errors();
                    throw new \Exception("Failed to parse XML response");
                }

                $prazoEntrega = $dom->getElementsByTagName('prazoEntrega')->item(0);
                $vlTotalFrete = $dom->getElementsByTagName('vlTotalFrete')->item(0);

                if (!empty($prazoEntrega) && !empty($prazoEntrega->nodeValue)) {
                    $this->response->tempo_previsto = $prazoEntrega->nodeValue;
                    $this->response->valor_total = $vlTotalFrete->nodeValue ?? 0;
                } else {
                    $this->response->exception = "TNT: Sem dados de prazo/valor na resposta";
                }
            } else {
                $this->response->exception = "TNT: Resposta vazia do servidor";
            }
        } catch (\Exception $e) {
            $this->response->exception = "TNT Exception: " . $e->getMessage();
        }

        return $this->response->toArray();
    }
}