<?php
declare(strict_types=1);

namespace App\CryptoGatewayEngine\WalletGenerator\Util;

use App\CryptoGatewayEngine\WalletGenerator\Util\Coin\CoinParams;
use BitWasp\Bitcoin\Network\Network;
use BitWasp\Bitcoin\Script\ScriptType;

class FlexNetwork extends Network
{
    function __construct(string $coin)
    {
        $network = 'main';
        if (strstr($coin, '-')) {
            list($coin, $network) = explode('-', $coin);
        }

        $params = CoinParams::getCoinNetwork($coin, $network);
        $prefixes = $params['prefixes'] ?? null;

        $scriptHash = $prefixes['scripthash2'] ?? $prefixes['scripthash'] ?? 0;

        $this->base58PrefixMap = [
            self::BASE58_ADDRESS_P2PKH => $this->decToHex($params['prefixes']['public'] ?? 0),
            self::BASE58_ADDRESS_P2SH  => $this->decToHex($scriptHash),
            self::BASE58_WIF           => $this->decToHex($params['prefixes']['private'] ?? 0),
        ];

        $this->bech32PrefixMap = [];
        if (isset($params['prefixes']['bech32'])) {
            $this->bech32PrefixMap[self::BECH32_PREFIX_SEGWIT] = $params['prefixes']['bech32'];
        }

        $this->bip32PrefixMap = [
            self::BIP32_PREFIX_XPUB => $this->transform($params['prefixes']['extended']['xpub']['public'] ?? null),
            self::BIP32_PREFIX_XPRV => $this->transform($params['prefixes']['extended']['xpub']['private'] ?? null),
        ];

        $this->bip32ScriptTypeMap = [
            self::BIP32_PREFIX_XPUB => ScriptType::P2PKH,
            self::BIP32_PREFIX_XPRV => ScriptType::P2PKH,
        ];

        $this->signedMessagePrefix = $params['message_magic'];

        $this->p2pMagic = $this->transform($params['protocol']['magic'] ?? '');
    }

    /**
     * Incoming values look like 0x1ec but bitwasp lib expects them like 01ec or ec instead.
     * This method drops the 0x and prepends 0 if necessary to make length an even number.
     */
    private function transform(?string $hex): string
    {
        $hex = (string)substr($hex, 2);
        $pre = strlen($hex) % 2 == 0 ? '' : '0';
        return $pre . $hex;
    }

    private function decToHex(?int $dec): string
    {
        $hex = dechex($dec);
        $pre = strlen($hex) % 2 == 0 ? '' : '0';
        return $pre . $hex;
    }
}
