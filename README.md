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


    public function doRequest()
    {
        $shipping = new \shippingCalculator\ShippingCostCalculator(
            "0000000000000",
            "0000000000000",
            "J", // ou "F"
            "",
            "00000000",
            "00000000000", //CNPJ ou CPF
            "Isento", 
            "J", // ou "F",
            "",
            "00000-000",
            "R",
            "Pago",
            985.50,
            15.8, //Kg
            [
                [
                  "height"   => 145
                  "width"    => 124.6
                  "depth"    => 124
                  "numBoxes" => 3
                ],
                [
                  "height"   => 121
                  "width"    => 100
                  "depth"    => 124
                  "numBoxes" => 1
                ]
            ]
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

