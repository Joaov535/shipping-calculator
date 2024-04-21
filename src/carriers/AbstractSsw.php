<?php

namespace shippingCalculator\carriers;

use shippingCalculator\ShippingCostCalculator;

abstract class AbstractSsw
{
    private string $companyName;
    public const ENDPOINT = 'https://ssw.inf.br/ws/sswCotacaoColeta/index.php?wsdl';
    private ShippingCostCalculator $shipping;
    private array $options;
    private array $requestBody;
    private \SplFixedArray $credentials;

    public function __construct(ShippingCostCalculator $shipping, array $credentials, string $companyName)
    {
        $this->shipping = $shipping;
        $this->companyName = $companyName;

        $this->credentials = new \SplFixedArray(3);
        $this->credentials['login'] = $credentials['login'];
        $this->credentials['password'] = $credentials['password'];
        $this->credentials['domain'] = $credentials['domain'];

        $this->options = [
            'cache_wsdl' => WSDL_CACHE_NONE,
            'trace' => 1,
            'stream_context' => stream_context_create(
                [
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true
                    ]
                ]
            )
        ];

        $this->setRequestBody();
    }

    public function setRequestBody(): void
    {
        $this->requestBody = [
            "dominio" => $this->credentials['domain'],
            "login" => $this->credentials['login'],
            "senha" => $this->credentials['password'],
            "cnpjPagador" => $this->shipping->getSenderCNPJ(),
            "cepOrigem" => $this->shipping->getSenderZipCode(),
            "cepDestino" => $this->shipping->getReceiverZipCode(),
            "valorNF" => $this->shipping->getSerialValue(),
            "quantidade" => $this->shipping->getNumTotalBoxes(),
            "peso" => $this->shipping->getTotalWeight(),
            "volume" => $this->shipping->getTotalVolume(),
            "mercadoria" => 1,
            "ciffob" => "C",
            "cnpjRemetente" => $this->shipping->getSenderCNPJ(),
            "cnpjDestinatario" => $this->shipping->getReceiverIdentification(),
            "observacao" => "",
            "trt" => ""
        ];
    }

    public function doRequest()
    {
        try {
            $client = new \SoapClient(self::ENDPOINT, $this->options);
            $xml = $client->cotar($this->requestBody);
            $sswQuotation = (array)simplexml_load_string($xml);

            if (!$sswQuotation['erro']) {
                $lowerTranspName = strtolower($this->companyName);
                $referenceCode = "code_quotation_{$lowerTranspName}_{$sswQuotation['cotacao']}";

                $quotation = array();
                $quotation['id'] = $referenceCode;
                $quotation['tempo_previsto'] = $sswQuotation['prazo'];
                $quotation['valor_total'] = str_replace(",", ".", $sswQuotation['frete']);
                $quotation['transportador'] = $this->companyName;
                return $quotation;

            }
        } catch (\Exception $e) {
            $request = [
                "erro" => "SSW: " . $e->getMessage()
            ];
        }
    }
}