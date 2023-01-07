<?php
declare(strict_types=1);

namespace App\CryptoGatewayEngine\WalletGenerator\Util\Coin;

use App\CryptoGatewayEngine\Exception\PaymentGatewayException;
use Exception;

class CoinParams
{

    /**
     * Returns information about a coin matching $symbol.
     *
     * @param string $symbol
     * @return mixed
     * @throws Exception
     */
    protected static function getCoin(string $symbol): array
    {
        $data = self::getAllCoins();
        $info = $data[strtoupper($symbol)] ?? null;

        if (!$info) {
            throw new PaymentGatewayException('Coin not found: ' . $symbol);
        }

        return $info;
    }

    /**
     * Returns information about coin + network
     *
     * @param string $symbol
     * @param string $network
     * @return array
     * @throws Exception
     */
    public static function getCoinNetwork(string $symbol, string $network): array
    {
        $data = self::getCoin($symbol);

        $info = $data[$network] ?? null;

        if (!$info) {
            throw new PaymentGatewayException('Network not found: ' . $symbol . '/' . $network);
        }

        return $info;
    }


    /**
     * Returns raw json text for all coins, data is read from disk each time called.
     *
     * @return string
     * @throws Exception
     */
    protected static function getRawJson(): string
    {
        $file = __DIR__ . '/coin-params.json';
        $buf = file_get_contents($file);

        if (!$buf) {
            throw new PaymentGatewayException('Unable to read file ' . $file);
        }

        return $buf;
    }

    /**
     * Returns parsed json data for all coins, data is cached between calls after initial read.
     *
     * @return array
     * @throws Exception
     */
    protected static function getAllCoins(): array
    {
        static $data = [];

        if ($data) {
            return $data;
        }

        $data = json_decode(self::getRawJson(), true);

        if (!$data) {
            throw new PaymentGatewayException('Unable to parse json: ' . json_last_error_msg());
        }

        return $data;
    }

}
