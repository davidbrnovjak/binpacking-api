<?php declare(strict_types=1);

namespace demo\BinPackingApi;


use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Psr\SimpleCache\CacheException;
use Psr\SimpleCache\CacheInterface;
use demo\BinPackingApi\Cache\MemoryCache;
use demo\BinPackingApi\Exception\ApiError;
use demo\BinPackingApi\Exception\ItemsTooBig;
use demo\BinPackingApi\Exception\PackingException;
use demo\BinPackingApi\Exception\InternalError;

class PackingWrapper implements LoggerAwareInterface
{
    private LoggerInterface $logger;

    private CacheInterface $cache;

    private PackingApiInterface $api;

    public function __construct(PackingApiInterface $api)
    {
        $this->logger = new NullLogger();
        $this->cache = new MemoryCache();
        $this->api = $api;
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * @param Product[] $products
     * @param Box[] $boxes
     * @throws ItemsTooBig
     * @throws ApiError
     * @throws PackingException
     */
    public function packItems(array $products, array $boxes): Box
    {
        if (count($boxes) === 0) {
            throw new InternalError('No boxes configured');
        }
        if (count($products) === 0) {
            return current($boxes); // @TODO - warn about bad usage?
        }

        // @TODO - don't convert to array yet
        $bins = $this->formatBoxesToApiBins($boxes);
        $items = $this->formatProductsToApiItems($products);

        $best_usable_bin = $this->getBestBin($bins, $items);

        $selected_box_array = array_filter($boxes, fn(Box $box) => $box->id === $best_usable_bin);
        if (count($selected_box_array) === 0) {
            throw new InternalError('Returned bin id doesnt match any Box');
        }

        return current($selected_box_array);
    }

    private function getBestBin(array $bins, array $items): string
    {
        $cache_key = $this->calcCacheKey($bins, $items);
        $cache_hit = $this->getBestFromCache($cache_key);
        if ($cache_hit !== null) {
            return $cache_hit;
        }

        $best_usable_bin = $this->api->calcBestBin($bins, $items);
        $this->saveBestToCache($cache_key, $best_usable_bin);
        return $best_usable_bin;
    }

    /**
     * @param Box[] $boxes
     */
    public function formatBoxesToApiBins(array $boxes): array
    {
        $bins = [];
        foreach ($boxes as $box) {
            $bins[] = [
                'w' => $box->width,
                'h' => $box->height,
                'd' => $box->depth,
                'id' => $box->id,
                'max_wg' => $box->max_weight ?? 0.0,
            ];
        }
        return $bins;
    }

    /**
     * @param Product[] $products
     */
    public function formatProductsToApiItems(array $products): array
    {
        $items = [];
        foreach ($products as $key => $product) {
            $items[] = [
                'w' => $product->width,
                'h' => $product->height,
                'd' => $product->length,
                'q' => 1,
                'vr' => 1,
                'id' => $key,
                'wg' => $product->weight ?? 0.0,
            ];
        }
        return $items;
    }

    private function calcCacheKey(array $bins, array $items): string
    {
        // @todo - different items with same dimensions would miss cache
        usort($items, fn(array $a, array $b) => $a['id'] <=> $b['id']);
        usort($bins, fn(array $a, array $b) => $a['id'] <=> $b['id']);
        return md5(json_encode($bins) . json_encode($items));
    }

    private function getBestFromCache(string $cache_key): ?string
    {
        try {
            return $this->cache->get($cache_key);
        } catch (CacheException $e) {
            $this->logger->warning('Cache get failed', ['exception' => $e]);
            return null;
        }
    }

    private function saveBestToCache(string $cache_key, string $best_usable_bin): void
    {
        try {
            $this->cache->set($cache_key, $best_usable_bin);
        } catch (CacheException $e) {
            $this->logger->warning('Store to cache failed', ['exception' => $e]);
        }
    }
}