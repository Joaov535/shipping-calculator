<?php

namespace shippingCalculator;

use shippingCalculator\carriers\AlfaTransportes;
use shippingCalculator\carriers\Atual;
use shippingCalculator\carriers\BauerExpress;
use shippingCalculator\carriers\Braspress;
use shippingCalculator\carriers\Cruzeiro;
use shippingCalculator\carriers\Excellence;
use shippingCalculator\carriers\Movvi;
use shippingCalculator\carriers\Rodonaves;
use shippingCalculator\carriers\Tnt;
use shippingCalculator\carriers\SaoMiguel;

class ShippingCostCalculator
{
    private string $senderCNPJ;
    private string $senderIE;
    private string $senderPersonType;
    private string $senderTributarySituation;
    private string $senderZipCode;

    private string $receiverIdentification;
    private string $receiverIE;
    private string $receiverPersonType;
    private string $receiverTributarySituation;
    private string $receiverZipCode;

    private string $transportType;
    private string $paymentStatus;
    private string $collectDate;
    private string $serialValue;
    private object $address;

    private int|float $totalWeight;
    private array $boxList;


    /**
     * @param string $senderCNPJ CNPJ do remetente
     * @param string $senderIE Inscrição estadual do remetente
     * @param string $senderPersonType J - jurídica ou F - física
     * @param string $senderTributarySituation
     * @param string $senderZipCode CEP do remetente
     * @param string $receiverIdentification CNPJ ou CPF do destinatário
     * @param string $receiverIE Inscrição estadual do destinatário
     * @param string $receiverPersonType J - jurídica ou F - física
     * @param string $receiverTributarySituation
     * @param string $receiverZipCode CEP do destinatário
     * @param string $transportType R - rodoviário ou A - aéreo
     * @param string $paymentStatus C - concluído ou P - pendente
     * @param string $collectDate Data do envio
     * @param string $serialValue Valor da mercadoria R$
     * @param float|int $totalWeight Peso total em gramas
     * @param array $boxList Lista com as medidas das caixas
     */
    public function __construct(
        string    $senderCNPJ,
        string    $senderIE,
        string    $senderPersonType,
        string    $senderTributarySituation,
        string    $senderZipCode,
        string    $receiverIdentification,
        string    $receiverIE,
        string    $receiverPersonType,
        string    $receiverTributarySituation,
        string    $receiverZipCode,
        string    $transportType,
        string    $paymentStatus,
        string    $collectDate,
        string    $serialValue,
        float|int $totalWeight,
        array     $boxList
    ) {
        $this->senderCNPJ = $senderCNPJ;
        $this->senderIE = $senderIE;
        $this->senderPersonType = $senderPersonType;
        $this->senderTributarySituation = $senderTributarySituation;
        $this->senderZipCode = $senderZipCode;
        $this->receiverIdentification = $receiverIdentification;
        $this->receiverIE = $receiverIE;
        $this->receiverPersonType = $receiverPersonType;
        $this->receiverTributarySituation = $receiverTributarySituation;
        $this->receiverZipCode = $receiverZipCode;
        $this->transportType = $transportType;
        $this->paymentStatus = $paymentStatus;
        $this->collectDate = $collectDate;
        $this->serialValue = $serialValue;
        $this->totalWeight = $totalWeight;
        $this->boxList = $boxList;
        $this->setAddressByCEP($receiverZipCode);
    }


    /**
     * @return string
     */
    public function getSenderCNPJ(): string
    {
        return preg_replace('/\D/', '', $this->senderCNPJ);
    }

    /**
     * @return string
     */
    public function getSenderIE(): string
    {
        return $this->senderIE;
    }


    /**
     * @return string
     */
    public function getSenderPersonType(): string
    {
        return $this->senderPersonType;
    }


    /**
     * @return string
     */
    public function getSenderTributarySituation(): string
    {
        return $this->senderTributarySituation;
    }


    /**
     * @return string
     */
    public function getSenderZipCode(): string
    {
        return preg_replace('/\D/', '', $this->senderZipCode);
    }


    /**
     * @return string
     */
    public function getReceiverIdentification(): string
    {
        return preg_replace('/\D/', '', $this->receiverIdentification);
    }


    /**
     * @return string
     */
    public function getReceiverIE(): string
    {
        return preg_replace('/[^0-9]/', '',  $this->receiverIE);
    }


    /**
     * @return string
     */
    public function getReceiverPersonType(): string
    {
        return $this->receiverPersonType;
    }


    /**
     * @return string
     */
    public function getReceiverTributarySituation(): string
    {
        return $this->receiverTributarySituation;
    }

    /**
     * @return string
     */
    public function getReceiverZipCode(): string
    {
        return preg_replace('/\D/', '', $this->receiverZipCode);
    }


    /**
     * @return string
     */
    public function getTransportType(): string
    {
        return $this->transportType;
    }

    /**
     * @return string
     */
    public function getPaymentStatus(): string
    {
        return $this->paymentStatus;
    }

    /**
     * @return string
     */
    public function getCollectDate(): string
    {
        return $this->collectDate;
    }

    /**
     * @return string
     */
    public function getSerialValue(): float
    {
        return floatval($this->serialValue);
    }

    /**
     * @return float|int
     */
    public function getTotalWeight(): float|int
    {
        if (is_float($this->totalWeight)) return round(floatval($this->totalWeight), 2);

        return $this->totalWeight;
    }

    /**
     * @return int
     */
    public function getNumTotalBoxes(): int
    {
        $n = 0;
        foreach ($this->boxList as $box) {
            $n += intval($box['numBoxes']);
        }
        return $n;
    }

    /**
     * @return array
     */
    public function getBoxList(): array
    {
        return $this->boxList;
    }

    public function getTotalVolume(): int|float
    {
        $acc = 0;
        foreach ($this->boxList as $box) {
            $m3 = (floatval($box['width']) * floatval($box['height']) * floatval($box['depth'])) * floatval($box['numBoxes']);
            $acc += $m3;
        }
        return $acc;
    }

    public function getTotalVolumeInMeters(): int|float
    {
        return $this->getTotalVolume() * pow(10, -6);
    }

    public function getBoxListInMeters(): array
    {
        $boxInMeters = [];
        foreach ($this->boxList as $box) {
            $boxM = [];
            $boxM['width'] = floatval($box['width']) / 100.0;
            $boxM['height'] = floatval($box['height']) / 100.0;
            $boxM['depth'] = floatval($box['depth']) / 100.0;
            $boxM['numBoxes'] = intval($box['numBoxes']);
            $boxInMeters[] = $boxM;
        }
        return $boxInMeters;
    }

    public function getAddressByCEP(): object
    {
        return $this->address;
    }

    private function setAddressByCEP(string $cep): void
    {
        $cep = preg_replace('/\D/', '', $cep);
        $url = "http://viacep.com.br/ws/$cep/xml/";

        $xml = simplexml_load_file($url);
        $this->address = $xml;
    }


    public function getBraspressShippingCost(string $token): array
    {
        $braspress = new Braspress($token, $this);
        return $braspress->doRequest();
    }

    /**
     * @param array $credentials ["user" => xxxxxx, "password" => xxxxxx]
     * @return array
     */
    public function getMovviShippingCost(array $credentials): array
    {
        $movvi = new Movvi($this, $credentials);
        return $movvi->doRequest();
    }

    /**
     * @param array $credentials ["user" => xxxxxx, "password" => xxxxxx]
     * @return array
     */
    public function getRodonavesShippingCost(array $credentials): array
    {
        $rodonaves = new Rodonaves($this, $credentials);
        return $rodonaves->doRequest();
    }

    /**
     * @param array $credentials ["login" => xxxxxx, "password" => xxxxxx]
     * @return array
     */
    public function getTntShippingCost(array $credentials = []): array
    {
        $tnt = new Tnt($this, $credentials);
        return $tnt->doRequest();
    }

    /**
     * @param array $credentials ["login" => xxxxx, "password" => xxxxx, "domain" => ]
     * @return array
     */
    public function getAtualShippingCost(array $credentials): array
    {
        $atual = new Atual($this, $credentials, true);
        return $atual->doRequest();
    }

    /**
     * @param array $credentials ["login" => xxxxx, "password" => xxxxx, "domain" => ]
     * @return array
     */
    public function getExcellenceShippingCost(array $credentials): array
    {
        $excellence = new Excellence($this, $credentials, true);
        return $excellence->doRequest();
    }

    public function getAlfaTransportesShippingCost(string $token): array
    {
        $company = new AlfaTransportes($this, $token);
        return $company->doRequest();
    }

    /**
     * @param array $credentials ["login" => xxxxx, "password" => xxxxx, "domain" => ]
     * @return array
     */
    public function getBauerShippingCost(array $credentials): array
    {
        $bauer = new BauerExpress($this, $credentials, true);
        return $bauer->doRequest();
    }

    /**
     * @param array $credentials ["login" => xxxxx, "password" => xxxxx]
     * @return array
     */
    public function getSaoMiguelCost(array $credentials)
    {
        $saoMiguel = new SaoMiguel($this, $credentials);
        return $saoMiguel->doRequest();
    }

    /**
     * @param array $credentials ["login" => xxxxx, "password" => xxxxx, "domain" => ]
     * @return array
     */
    public function getCruzeiroShippingCost(array $credentials): array
    {
        $bauer = new Cruzeiro($this, $credentials, true);
        return $bauer->doRequest();
    }

    public function toArray(): array
    {
        return [
            'senderCNPJ' => $this->senderCNPJ,
            'senderIE' => $this->senderIE,
            'senderPersonType' => $this->senderPersonType,
            'senderTributarySituation' => $this->senderTributarySituation,
            'senderZipCode' => $this->senderZipCode,
            'receiverIdentification' => $this->receiverIdentification,
            'receiverIE' => $this->receiverIE,
            'receiverPersonType' => $this->receiverPersonType,
            'receiverTributarySituation' => $this->receiverTributarySituation,
            'receiverZipCode' => $this->receiverZipCode,
            'transportType' => $this->transportType,
            'paymentStatus' => $this->paymentStatus,
            'collectDate' => $this->collectDate,
            'serialValue' => $this->serialValue,
            'totalWeight' => $this->totalWeight,
            'numTotalBoxes' => $this->getNumTotalBoxes(),
            'boxList' => $this->boxList,
        ];
    }
}
