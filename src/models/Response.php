<?php
/**
 * @author João V. Cruz
 * @date 04/10/2024
 */

namespace shippingCalculator\models;

class Response
{
    public ?string $transportador;
    public ?string $tempo_previsto;
    public ?float $valor_total;
    public ?string $exception;

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}