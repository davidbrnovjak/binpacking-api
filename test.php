<?php declare(strict_types=1);

require 'vendor/autoload.php';

use GuzzleHttp\Client;
use demo\BinPackingApi\Box;
use demo\BinPackingApi\Exception\ApiError;
use demo\BinPackingApi\Exception\ItemsTooBig;
use demo\BinPackingApi\Exception\PackingException;
use demo\BinPackingApi\PackingApi;
use demo\BinPackingApi\PackingWrapper;
use demo\BinPackingApi\Product;


$client = new Client(['base_uri' => 'https://global-api.3dbinpacking.com/packer/']);
$api = new PackingApi('david.brnovjak@gmail.com', '80b361b33bbd1b2d430f234740c0b945', $client);
$packer = new PackingWrapper($api);

$boxes = [
    new Box('S', 1.0, 1.0, 1.0, 5.0),
    new Box('M', 10.0, 10.0, 10.0, 5.0),
    new Box('L', 25.0, 25.0, 10.0, 10.0),
    new Box('XL', 30.0, 25.0, 10.0, null),
];

$items = [
    new Product(11.0, 2.0, 5.0, 2.0),
    new Product(1.0, 1.0, 1.0, null),
];

if (count($items) === 0) {
    var_dump("You havent selected any items to ship. Do some shopping first..");
    exit;
}

try {
    $box = $packer->packItems($items, $boxes);
    var_dump("Let's use the {$box->id} box.");

} catch (ItemsTooBig $e) {
    var_dump("Sorry. It seems none ouf our packaging options is big enough.");

} catch (ApiError $e) {
    var_dump("Sorry. we're unable to calculate the optimal packaging right now but you may get lucky if you try again!");

} catch (PackingException $e) {
    var_dump("Sorry, we're unable to calculate the optimal packaging for you.");

} catch (\Throwable $e) {
    var_dump("We screwed up..");
}


