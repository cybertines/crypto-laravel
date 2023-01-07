<?php
declare(strict_types=1);

namespace App\CryptoGatewayEngine\Monitor\BlockStorage;

use Illuminate\Support\Facades\Cache;

class CacheBlockStorageInterface implements BlockStorageContractInterface
{
    public function getBlock(string $key): ?int
    {
        return Cache::get($key);
    }

    public function setBlock(string $key, int $value, int $ttl = 0): void
    {
        Cache::put($key, $value, $ttl);
    }
}
