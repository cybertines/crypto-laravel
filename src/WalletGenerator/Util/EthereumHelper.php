<?php
declare(strict_types=1);

namespace App\CryptoGatewayEngine\WalletGenerator\Util;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Crypto\EcAdapter\EcAdapterFactory;
use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\PhpEcc\Serializer\Key\PublicKeySerializer;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PublicKeyInterface;
use Mdanter\Ecc\Serializer\Point\UncompressedPointSerializer;

class EthereumHelper
{
    public static function getEthereumAddress(PublicKeyInterface $publicKey): string
    {
        static $pubKeySerializer = null;
        static $pointSerializer = null;

        if (!$pubKeySerializer) {
            $adapter = EcAdapterFactory::getPhpEcc(Bitcoin::getMath(), Bitcoin::getGenerator());
            $pubKeySerializer = new PublicKeySerializer($adapter);
            $pointSerializer = new UncompressedPointSerializer();
        }

        $pubKey = $pubKeySerializer->parse($publicKey->getBuffer());
        $upk = $pointSerializer->serialize($pubKey->getPoint());
        $upk = hex2bin(substr($upk, 2));

        $keccak = Keccak::hash($upk, 256);
        $ethAddressLower = strtolower(substr($keccak, -40));

        $hash = Keccak::hash($ethAddressLower, 256);
        $ethAddress = '';
        for ($i = 0; $i < 40; $i++) {
            $char = substr($ethAddressLower, $i, 1);

            if (ctype_digit($char))
                $ethAddress .= $char;
            else if ('0' <= $hash[$i] && $hash[$i] <= '7')
                $ethAddress .= strtolower($char);
            else
                $ethAddress .= strtoupper($char);
        }

        return $ethAddress;
    }

    public static function addEthSuffix(string $string): string
    {
        return '0x' . $string;
    }

    public static function getFullEthereumAddress(PublicKeyInterface $publicKey): string
    {
        return self::addEthSuffix(self::getEthereumAddress($publicKey));
    }

}
