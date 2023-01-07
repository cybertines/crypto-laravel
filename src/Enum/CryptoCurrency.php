<?php
declare(strict_types=1);

namespace App\CryptoGatewayEngine\Enum;

use BenSampo\Enum\Enum;

/**
 * @method static static BTC();
 * @method static static LTC();
 * @method static static XRP();
 * @method static static ETH();
 * @method static static USDT();
 * @method static static TRX();
 */
final class CryptoCurrency extends Enum
{
    public const BTC = 'btc';
    public const LTC = 'ltc';
    public const XRP = 'xrp';
    public const ETH = 'eth';
    public const USDT = 'usdt';
    public const TRX = 'trx';
}
