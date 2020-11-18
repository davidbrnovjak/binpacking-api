<?php declare(strict_types=1);


namespace demo\BinPackingApi\Exception;


class ItemsTooBig extends PackingException
{
    public function __construct()
    {
        parent::__construct('No box is big enough');
    }
}