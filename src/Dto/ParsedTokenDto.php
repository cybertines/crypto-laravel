<?php
declare(strict_types=1);

namespace App\CryptoGatewayEngine\Dto;

use Spatie\DataTransferObject\DataTransferObject;

class ParsedTokenDto extends DataTransferObject
{
    public string $address;
    public float $value;
}
