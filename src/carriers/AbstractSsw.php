<?php

namespace shippingCalculator\carriers;

use shippingCalculator\ShippingCostCalculator;

abstract class AbstractSsw implements CarriersInterface
{
    private string $companyName;
    public const ENDPOINT = 'https://ssw.inf.br/ws/sswCotacaoColeta/index.php?wsdl';
    private ShippingCostCalculator $shipping;
    private array $options;
    private array $credentials;
    private bool $inMeters;
    protected int $commodity = 1;

    public function __construct(ShippingCostCalculator $shipping, array $credentials, string $companyName, bool $inMeters = false)
    {
        $this->inMeters = $inMeters;
        $this->shipping = $shipping;
        $this->companyName = $companyName;

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
    }


    public function doRequest()
    {
        $quotation = [];

        try {
            $client = new \SoapClient(self::ENDPOINT, $this->options);
            $xml = $client->cotar(
                $this->credentials['domain'],
                $this->credentials['login'],
                $this->credentials['password'],
                $this->shipping->getSenderCNPJ(),
                $this->shipping->getSenderZipCode(),
                $this->shipping->getReceiverZipCode(),
                $this->shipping->getSerialValue(),
                $this->shipping->getNumTotalBoxes(),
                $this->shipping->getTotalWeight(),
                $this->inMeters ? $this->shipping->getTotalVolumeInMeters() : $this->shipping->getTotalVolume(),
                $this->commodity,
                "C",
                $this->shipping->getSenderCNPJ(),
                $this->shipping->getReceiverIdentification(),
                "",
                ""
            );

            $sswQuotation = (array)simplexml_load_string($xml);

            $quotation['id'] = $this->companyName . '_' . time();
            $quotation['transportador'] = $this->companyName;

            if (!$sswQuotation['erro']) {
                $quotation['tempo_previsto'] = $sswQuotation['prazo'];
                $quotation['valor_total'] = str_replace(",", ".", $sswQuotation['frete']);
            } else {
                $quotation['tempo_previsto'] = $sswQuotation['mensagem'];
                $quotation['valor_total'] = 0;
            }
        } catch (\Exception $e) {
            return [
                "erro" => "SSW: " . $e->getMessage()
            ];
        }

        return $quotation;
    }
}