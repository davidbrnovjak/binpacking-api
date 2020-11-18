<?php declare(strict_types=1);


namespace demo\BinPackingApi;


interface PackingApiInterface
{

    public function calcBestBin(array $bins, array $items): string;

}