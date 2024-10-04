<?php

namespace shippingCalculator\abstractions;

use shippingCalculator\contracts\CarriersInterface;
use shippingCalculator\ShippingCostCalculator;

abstract class AbstractSsw extends AbstractCarriers
{
    public const ENDPOINT = 'https://ssw.inf.br/ws/sswCotacaoColeta/index.php?wsdl';
    protected array $options;
    protected bool $inMeters;
    protected int $commodity = 1;

    public function __construct(ShippingCostCalculator $shipping, array $credentials, bool $inMeters = false)
    {
        parent::__construct();
        $this->inMeters = $inMeters;
        $this->shipping = $shipping;
        $this->setCompanyName();
        $this->setCredentials($credentials);

        $this->options = [
            'cache_wsdl' => WSDL_CACHE_NONE,
            'trace' => 1,
            'stream_context' => stream_context_create(
                [
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true
                    ],
                    'http' => [
                        'encoding' => 'utf-8'
                    ]
                ]
            )
        ];
    }

    protected function setCredentials(array|string $credentials): void
    {
        $this->credentials['login'] = $credentials['login'];
        $this->credentials['password'] = $credentials['password'];
        $this->credentials['domain'] = $credentials['domain'];
    }

    public function doRequest(): array
    {
        try {
            $this->response->transportador = $this->companyName;

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

            if (isset($sswQuotation['mensagem']) && $sswQuotation['mensagem'] != "OK") throw new \Exception($sswQuotation['mensagem']);
            if (empty($sswQuotation['prazo'])) throw new \Exception("Prazo não retornado");
            if (empty($sswQuotation['frete'])) throw new \Exception("Valor do frete não retornado");

            $this->response->tempo_previsto = $sswQuotation['prazo'];
            $value = str_replace(".", "", $sswQuotation['frete']);
            $value = str_replace(",", ".", $value);
            $this->response->valor_total = floatval(floatval($value));

        } catch (\Exception $e) {
            $this->response->exception = $e->getMessage();
        }

        return $this->response->toArray();
    }
}