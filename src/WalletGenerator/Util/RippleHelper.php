<?php
declare(strict_types=1);

namespace App\CryptoGatewayEngine\WalletGenerator\Util;

class RippleHelper
{
    public static function getAddress(string $address): string
    {
        $decode = Base58::decode($address);
        Base58::setBase('rpshnaf39wBUDNEGHJKLM4PQRST7VWXYZ2bcdeCg65jkm8oFqi1tuvAxyz');
        return Base58::encode($decode, false);
    }

    public static function getPrivateKey(string $key): string
    {
        Base58::setBase('123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz');
        return Base58::decode($key)->slice(1, 32)->getHex();
    }
}
