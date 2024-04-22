<?php

namespace shippingCalculator;

use shippingCalculator\carriers\Braspress;
use shippingCalculator\carriers\Movvi;

class ShippingCostCalculator
{
    private int $senderCNPJ;
    private int $senderIE;
    private string $senderPersonType;
    private string $senderTributarySituation;
    private int $senderZipCode;

    private int $receiverIdentification;
    private int $receiverIE;
    private string $receiverPersonType;
    private string $receiverTributarySituation;
    private int $receiverZipCode;

    private string $transportType;
    private string $paymentStatus;
    private string $collectDate;
    private int $serialValue;

    private int|float $totalWeight;
    private int $numTotalBoxes;
    private array $boxList;


    /**
     * @param int $senderCNPJ CNPJ do remetente
     * @param int $senderIE Inscrição estadual do remetente
     * @param string $senderPersonType J - jurídica ou F - física
     * @param string $senderTributarySituation
     * @param int $senderZipCode CEP do remetente
     * @param int $receiverIdentification CNPJ ou CPF do destinatário
     * @param int $receiverIE Inscrição estadual do destinatário
     * @param string $receiverPersonType J - jurídica ou F - física
     * @param string $receiverTributarySituation
     * @param int $receiverZipCode CEP do destinatário
     * @param string $transportType R - rodoviário ou A - aéreo
     * @param string $paymentStatus C - concluído ou P - pendente
     * @param string $collectDate Data do envio
     * @param int $serialValue Valor da mercadoria R$
     * @param float|int $totalWeight Peso total em gramas
     * @param int $numTotalBoxes Quantidade de caixas
     * @param array $boxList Lista com as medidas das caixas
     */
    public function __construct(
        int       $senderCNPJ,
        int       $senderIE,
        string    $senderPersonType = 'J',
        string    $senderTributarySituation,
        int       $senderZipCode,
        int       $receiverIdentification,
        int       $receiverIE,
        string    $receiverPersonType,
        string    $receiverTributarySituation,
        int       $receiverZipCode,
        string    $transportType,
        string    $paymentStatus,
        string    $collectDate,
        int       $serialValue,
        float|int $totalWeight,
        int       $numTotalBoxes,
        array     $boxList
    )
    {
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
        $this->numTotalBoxes = $numTotalBoxes;
        $this->boxList = $boxList;
    }


    /**
     * @return int
     */
    public function getSenderCNPJ(): int
    {
        return $this->senderCNPJ;
    }

    /**
     * @return int
     */
    public function getSenderIE(): int
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
     * @return int
     */
    public function getSenderZipCode(): int
    {
        return $this->senderZipCode;
    }


    /**
     * @return int
     */
    public function getReceiverIdentification(): int
    {
        return $this->receiverIdentification;
    }


    /**
     * @return int
     */
    public function getReceiverIE(): int
    {
        return $this->receiverIE;
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
     * @return int
     */
    public function getReceiverZipCode(): int
    {
        return $this->receiverZipCode;
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
     * @return int
     */
    public function getSerialValue(): int
    {
        return $this->serialValue;
    }

    /**
     * @return float|int
     */
    public function getTotalWeight(): float|int
    {

        if(empty($this->totalWeight)) {
            $this->totalWeight = 0;
            foreach ($this->boxList as $box) {
                $this->totalWeight += $box['weight'];
            }
        }
        return $this->totalWeight;
    }

    /**
     * @return int
     */
    public function getNumTotalBoxes(): int
    {
        return $this->numTotalBoxes;
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
            $m3 = $box['width'] * $box['height'] * $box['depth'];
            $acc += $m3;
        }
        return $acc;
    }

    public function getBoxListInMeters(): array
    {
        $boxInMeters = [];
        foreach ($this->boxList as $box) {
            $boxM = [];
            $boxM['width'] = $box['width'] * 100;
            $boxM['height'] = $box['height'] * 100;
            $boxM['depth'] = $box['depth'] * 100;
            $boxM['weight'] = $box['weight'];
            $boxInMeters[] = $boxM;
        }
        return $boxInMeters;
    }


    public function getBraspressShippingCost(string $token): array
    {
        $company = new \shippingCalculator\carriers\Braspress($token, $this);
        return $company->doRequest();
    }


    public function getMovviShippingCost(): array
    {

    }


    public function getRodonavesShippingCost(): array
    {

    }


    public function getTntShippingCost(): array
    {

    }


    public function getJundiaiShippingCost(): array
    {

    }

    public function getAtualShippingCost(): array
    {

    }

    public function getAlfaTransportesShippingCost(string $token): array
    {
        $company = new \shippingCalculator\carriers\AlfaTransportes($this, $token);
        return $company->doRequest();
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
            'numTotalBoxes' => $this->numTotalBoxes,
            'boxList' => $this->boxList,
        ];
    }
}
