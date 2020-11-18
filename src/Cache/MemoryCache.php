<?php declare(strict_types=1);

namespace demo\BinPackingApi\Cache;


use Psr\SimpleCache\CacheInterface;

class MemoryCache implements CacheInterface
{
    private array $cache = [];

    public function get($key, $default = null)
    {
        return @$this->cache[$key];
    }

    public function set($key, $value, $ttl = null)
    {
        $this->cache[$key] = $value;
        return true;
    }

    public function delete($key)
    {
        unset($this->cache[$key]);
        return true;
    }

    public function clear()
    {
        $this->cache = [];
        return true;
    }

    public function getMultiple($keys, $default = null)
    {
        throw new \Exception('Not implemented');
    }

    public function setMultiple($values, $ttl = null)
    {
        throw new \Exception('Not implemented');
    }

    public function deleteMultiple($keys)
    {
        throw new \Exception('Not implemented');
    }

    public function has($key)
    {
        return array_key_exists($key, $this->cache);
    }
}