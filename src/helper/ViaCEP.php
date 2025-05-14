<?php

namespace shippingCalculator\helper;

/**
 * Classe responsável por 
 *
 * @author João Victor Cruz
 * @date 23 de abr. de 2025
 */
class ViaCEP {

    private function __construct() {
        
    }

    /**
     * Retorna um objeto contendo as informações do CEP
     * 
     * @param string $cep
     * @return object
     *     @property string $cep
     *     @property string $logradouro
     *     @property string $complemento
     *     @property string $unidade
     *     @property string $bairro
     *     @property string $localidade
     *     @property string $uf
     *     @property string $estado
     *     @property string $regiao
     *     @property string $ibge
     *     @property string $gia
     *     @property string $ddd
     *     @property string $siafi
     * 
     * @throws \Exception
     */
    public static function getCityInfo(string $cep): object {
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => "http://viacep.com.br/ws/{$cep}/json/",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET"
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            throw new \Exception("cURL Error #:" . $err);
        } else {
            return json_decode($response);
        }
    }

    /**
     * Retorna o código do IBGE referente ao município do CEP
     * 
     * @param string $cep
     */
    public static function getCityCodeIBGE(string $cep): string {
        $res = static::getCityInfo($cep);

        return $res->ibge;
    }
}
