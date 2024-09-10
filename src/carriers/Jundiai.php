<?php

namespace shippingCalculator\carriers;

use shippingCalculator\ShippingCostCalculator;

class Jundiai implements CarriersInterface
{

    public const WSDL = "https://newsitex.expressojundiai.com.br/NewSitex/WebServices/wsFreteCombinado.asmx?WSDL";
    private string $token;
    private string $companyName = "Expresso Jundiaí";
    private ShippingCostCalculator $shipping;
    private array $requestBody;


    public function __construct(ShippingCostCalculator $shipping, string $token)
    {
        $this->shipping = $shipping;
        $this->token = $token;
        $this->setRequestBody();
    }

    private function setRequestBody(): void
    {
        $this->requestBody = [
            "token" => $this->token,
            "cnpjRemetente" => $this->shipping->getSenderCNPJ(),
            "ieRemetente" => $this->shipping->getSenderIE(),
            "cnpjDestinatario" => $this->shipping->getReceiverIdentification(),
            "ieDestinatario" => $this->shipping->getReceiverIE(),
            "flDestinatarioConsumidorFinal" => true,
            "cnpjTomador" => $this->shipping->getSenderCNPJ(),
            "ieTomador" => $this->shipping->getSenderIE(),
            "cnpjRecebedor" => '',
            "ieRecebedor" => '',
            "observacao" => '',
            "dataEmbarque" => $this->shipping->getCollectDate(),
            "placaVeiculo" => '',
            "kmViagem" => '0.0',
            "flArmazemGeral" => false,
            "flIncideIcms" => true,
            "ufOrigem" => '',
            "cepOrigem" => $this->shipping->getSenderZipCode(),
            "ufEntrega" => $this->shipping->getAddressByCEP()->uf,
            "cepEntrega" => $this->shipping->getReceiverZipCode(),
            "codCondicaoContrato" => 0,
            "pesoNominal" => $this->shipping->getTotalWeight(),
            "cubagem" => $this->shipping->getTotalVolumeinMeters(),
            "vlMercadoria" => $this->shipping->getSerialValue(),
            "qtVolumes" => $this->shipping->getNumTotalBoxes(),
            "codEmbagem" => 0,
            "codProduto" => 1,
            "idTipoCalculoFrete" => 1,
            "flNFDevolucao" => false,
            "codTipoServico" => 1
        ];
    }

    public function doRequest(): array
    {
        $client = new \SoapClient(self::WSDL);
        try {
            $xmlResponse = $client->__soapCall("FreteCombinado", [$this->requestBody]);
        } catch (\Exception) {
            $quotation['id'] = $this->companyName . '_' . time();
            $quotation['tempo_previsto'] = "Falha ao realizar requisição";
            $quotation['valor_total'] = 0;
            $quotation['transportador'] = $this->companyName;

            return $quotation;
        }

        if (!isset($xmlResponse->FreteCombinadoResult->any)) {
            $quotation['id'] = $this->companyName . '_' . time();
            $quotation['tempo_previsto'] = "Sem Resposta";
            $quotation['valor_total'] = 0;
            $quotation['transportador'] = $this->companyName;

            return $quotation;
        }

        $jundiaiQuotation = (array)simplexml_load_string($xmlResponse->FreteCombinadoResult->any);

        $quotation = array();
        if (isset($jundiaiQuotation['vlFrete'])) {

            if (isset($jundiaiQuotation['dtPrevisaoEntrega'])) {
                $currentDate = new \DateTime(date('Y-m-d'));
                $deliveryDate = new \DateTime(date('Y-m-d', strtotime($jundiaiQuotation['dtPrevisaoEntrega'])));
                $interval = $currentDate->diff($deliveryDate);
                $daysToDelivery = $interval->format('%d');
            } elseif (isset($jundiaiQuotation['qtDiasPrevisaoEntrega'])) {
                $daysToDelivery = $jundiaiQuotation['qtDiasPrevisaoEntrega'];
            }

            $quotation['id'] = $this->companyName . '_' . time();
            $quotation['tempo_previsto'] = $daysToDelivery;
            $quotation['valor_total'] = $jundiaiQuotation['vlFrete'];
            $quotation['transportador'] = $this->companyName;
        }

        return $quotation;
    }
}
