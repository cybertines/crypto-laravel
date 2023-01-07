<?php
declare(strict_types=1);

namespace App\CryptoGatewayEngine\Monitor\BlockStorage;

interface BlockStorageContractInterface
{
    public function getBlock(string $key): ?int;

    public function setBlock(string $key, int $value, int $ttl = 0): void;
}
