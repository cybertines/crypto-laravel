<?php
declare(strict_types=1);

namespace App\CryptoGatewayEngine\Dto;

use Spatie\DataTransferObject\DataTransferObject;

class CoinDto extends DataTransferObject
{
    public string $symbol;
    public string $network;
}
