<?php
declare(strict_types=1);

namespace App\CryptoGatewayEngine\Enum;

use BenSampo\Enum\Enum;

/**
 * @method static static DEFAULT()
 * @method static static ERC20()
 * @method static static TRC20()
 */
final class TokenType extends Enum
{
    public const DEFAULT = 'default';
    public const ERC20 = 'ERC-20';
    public const TRC20 = 'TRC-20';
}
