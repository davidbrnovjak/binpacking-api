<?php declare(strict_types=1);


namespace demo\BinPackingApi;


use demo\BinPackingApi\Exception\PackingException;
use demo\BinPackingApi\Exception\InvalidArgument;

class Product
{

    public float $width;
    public float $height;
    public float $length;
    public ?float $weight;

    public function __construct(
        float $width,
        float $height,
        float $length,
        ?float $weight
    ) {
        $this->width = $width;
        $this->height = $height;
        $this->length = $length;
        $this->weight = $weight;

        $dimension_args = [
            $width,
            $height,
            $length,
            $weight,
        ];

        foreach ($dimension_args as $arg) {
            if ($arg !== null && $arg <= 0) {
                throw InvalidArgument::invalidDimension();
            }
        }
    }
}