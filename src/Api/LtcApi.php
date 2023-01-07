<?php
declare(strict_types=1);

namespace App\CryptoGatewayEngine\Api;

use App\CryptoGatewayEngine\Api\Combine\NownodesApi;

class LtcApi extends NownodesApi
{
    /**
     * Method for NownodesApi
     * @return string
     */
    protected function defineNodeUrl(): string
    {
        return $this->testnet ? 'ltc-testnet' : 'ltc';
    }
}
