# Cotação de Frete

Essa biblioteca foi criada com o intuito de centralizar cotações de frete com transportadoras.

Até o momento as transportadoras integradas são: 
- Braspress
- Rodonaves
- TNT
- Excellence
- Alfa Transportes
- Atual
- Expresso Jundiaí.

```php
<?php

class Consumer
{
    private const ALFA_TRANSPORTES_TOKEN = "xxxxxxxxxxxxxxxxxxxxxx";
    private const EXPRESSO_JUNDIAI_TOKEN = "xxxxxxxxxxxxxxxxxxxxxx";
    private const BRASPRESS_TOKEN = "xxxxxxxxxxxxxxxxxxxxx";
    private $credentials_movvi = ["user" => 'xxxxxxxxxxxxx', "password" => 'xxxxxxxxxx'];
    private $credentials_rodonaves = ["user" => 'xxxxxxxx', "password" => 'xxxxxxxxxxx'];
    private $credentials_tnt = ["login" => "xxxxxxxxxx", "password" => "xxxxxxxxxxxx"];
    private $credentials_excellence = ["login" => "xxxxxxxxxx", "password" => "xxxxxxxx", "domain" => "EXN"];
    private $credentials_atual = ["login" => "xxxxxxxxxx", "password" => "xxxxxxxx", "domain" => "ACT"];


    public function doRequest($data)
    {
        $shipping = new \shippingCalculator\ShippingCostCalculator(
            "0000000000000",
            "0000000000000",
            "J",
            "",
            "00000000",
            $data->receiverId,
            $data->ie,
            substr($data->typePerson, 0, 1),
            "",
            $data->cep,
            "R",
            $data->statusPayment,
            $data->colectDate,
            $data->priceNote,
            $data->totalWeight,
            $this->toArray($data->boxList)
        );


        $res = [];
        $res[] = $shipping->getBraspressShippingCost(self::BRASPRESS_TOKEN);
        $res[] = $shipping->getRodonavesShippingCost($this->credentials_rodonaves);
        $res[] = $shipping->getTntShippingCost($this->credentials_tnt);
        $res[] = $shipping->getMovviShippingCost($this->credentials_movvi);
        $res[] = $shipping->getExcellenceShippingCost($this->credentials_excellence);
        $res[] = $shipping->getAtualShippingCost($this->credentials_atual);
        $res[] = $shipping->getAlfaTransportesShippingCost(self::ALFA_TRANSPORTES_TOKEN);
        $res[] = $shipping->getJundiaiShippingCost(self::EXPRESSO_JUNDIAI_TOKEN);

        return $res;
    }

    private function toArray($data)
    {
        $arr = [];
        foreach ($data as $box) {
            $arr[] = get_object_vars($box);
        }
        return $arr;
    }
}
?>

