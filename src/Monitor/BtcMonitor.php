<?php
declare(strict_types=1);

namespace App\CryptoGatewayEngine\Monitor;

use App\CryptoGatewayEngine\Monitor\Combine\NownodesMonitor;

class BtcMonitor extends NownodesMonitor
{
    protected function getCustomBlockStart(): int
    {
        return (int) ($this->config['btc']['custom_start_block'] ?? 0);
    }

    protected function getMonitorTtlCache(): int
    {
        return (int) ($this->config['btc']['monitor_ttl_cache'] ?? 3600);
    }

    protected function getKeyCache(): string
    {
        return 'last_btc_block';
    }
}
