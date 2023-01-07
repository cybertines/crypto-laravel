<?php
declare(strict_types=1);

namespace App\CryptoGatewayEngine\Dto;

use Spatie\DataTransferObject\DataTransferObject;

class DestinationAddressDto extends DataTransferObject
{
    public string $address;
    public float $amount;
    /** @var string|null $tag - at this moment used for xrp as DestinationTag (UInt32) */
    public ?string $tag = null;
}
