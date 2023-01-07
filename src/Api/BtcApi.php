<?php
declare(strict_types=1);

namespace App\CryptoGatewayEngine\Api;

use App\CryptoGatewayEngine\Api\Combine\NownodesApi;

class BtcApi extends NownodesApi
{
    /**
     * Method for NownodesApi
     * @return string
     */
    protected function defineNodeUrl(): string
    {
        return $this->testnet ? 'btc-testnet' : 'btc';
    }
}
