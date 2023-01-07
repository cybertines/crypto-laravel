<?php
declare(strict_types=1);

namespace App\CryptoGatewayEngine\Transaction;

use App\CryptoGatewayEngine\Transaction\Combine\BitcoinFamilyTransaction;
use BitWasp\Bitcoin\Network\{NetworkFactory, NetworkInterface};

class BtcTransaction extends BitcoinFamilyTransaction
{
    protected function getNetworkFactory(bool $isTest = false): NetworkInterface
    {
        return $isTest ? NetworkFactory::bitcoinTestnet() : NetworkFactory::bitcoin();
    }
}
