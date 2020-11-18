<?php declare(strict_types=1);

namespace demo\BinPackingApi;


use demo\BinPackingApi\Exception\PackingException;
use demo\BinPackingApi\Exception\InvalidArgument;

class Box
{

    public string $id;
    public float $width;
    public float $height;
    public float $depth;
    public ?float $max_weight;

    public function __construct(
        string $id,
        float $width,
        float $height,
        float $depth,
        ?float $max_weight
    ) {
        $this->id = $id;
        $this->width = $width;
        $this->height = $height;
        $this->depth = $depth;
        $this->max_weight = $max_weight;

        $dimension_args = [
            $width,
            $height,
            $depth,
            $max_weight,
        ];

        foreach ($dimension_args as $arg) {
            if ($arg !== null && $arg <= 0) {
                throw InvalidArgument::invalidDimension();
            }
        }
    }

}