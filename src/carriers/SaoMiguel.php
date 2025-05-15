<?php

namespace shippingCalculator\carriers;

/**
 * @author João Victor Cruz
 * @date 23 de abr. de 2025
 */
class SaoMiguel extends \shippingCalculator\abstractions\AbstractCarriers
{

    protected const API = "https://wsintegcli01.expressosaomiguel.com.br:40504/wsservernet/rest/frete/buscar/cliente";

    protected string $customer;
    protected string $accessKey;
    protected string $requestBody;

    public function __construct(\shippingCalculator\ShippingCostCalculator $shipping, array $credentials)
    {
        parent::__construct();
        $this->shipping = $shipping;
        $this->setCompanyName();
        $this->setCredentials($credentials);
    }

    protected function setCompanyName(): void
    {
        $this->companyName = "São Miguel";
    }

    protected function setCredentials($credentials)
    {
        $this->accessKey = $credentials['password'];
        $this->customer = $credentials['login'];
    }

    protected function setBodyRequest()
    {
        $arr = [
            "tipoPagoPagar" => "P",
            "codigoCidadeDestino" => \shippingCalculator\helper\ViaCEP::getCityInfo($this->shipping->getReceiverZipCode())->ibge,
            "quantidadeMercadoria" => $this->shipping->getNumTotalBoxes(),
            "pesoMercadoria" => $this->shipping->getTotalVolumeInMeters(),
            "valorMercadoria" => $this->shipping->getSerialValue(),
            "tipoPeso" => "M",
            "clienteDestino" => $this->shipping->getReceiverIdentification(),
            "dataEmbarque" => (new \DateTime($this->shipping->getCollectDate()))->format('d/m/Y'),
            "tipoPessoaDestino" => $this->shipping->getReceiverPersonType()
        ];

        $this->requestBody = json_encode($arr);
    }

    public function doRequest(): array
    {
        $this->response->transportador = $this->companyName;

        try {
            $this->setBodyRequest();
            $curl = curl_init();

            curl_setopt_array($curl, [
                CURLOPT_PORT => "40504",
                CURLOPT_URL => self::API,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => $this->requestBody,
                CURLOPT_HTTPHEADER => [
                    "ACCESS_KEY: " . $this->accessKey,
                    "CUSTOMER: " . $this->customer,
                    "Content-Type: application/json",
                    "VERSION: 2"
                ],
            ]);

            $response = json_decode(curl_exec($curl));
            $err = curl_error($curl);

            curl_close($curl);

            if ($err) {
                throw new \Exception("cURL Error #:" . $err);
            }

            if ($response->status != "ok") {
                throw new \Exception($response->mensagem);
            }

            $date1 = \DateTime::createFromFormat('d/m/Y', substr($response->previsaoEntrega, 0, 10));
            $date2 = \DateTime::createFromFormat('d/m/Y', $response->previsaoEmbarque);

            $diff = $date1->diff($date2);

            $this->response->tempo_previsto = $diff->format('%a');
            $this->response->valor_total = $response->valorFrete;
        } catch (\Exception $e) {
            $this->response->exception = $e->getMessage();
        }

        return $this->response->toArray();
    }
}
