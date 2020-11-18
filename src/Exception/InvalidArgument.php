<?php declare(strict_types=1);


namespace demo\BinPackingApi\Exception;


class InvalidArgument extends PackingException
{

    public static function invalidDimension(): self
    {
        return new self('All dimensions should be greater than 0.');
    }

}