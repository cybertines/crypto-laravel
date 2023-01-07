<?php
declare(strict_types=1);

namespace App\CryptoGatewayEngine\Dto;

use Spatie\DataTransferObject\DataTransferObject;

class WalletDto extends DataTransferObject
{
    public string $coin;
    public string $address;
    public string $pubKey;
    public string $privateKey;
    public string $xPrv;
    public string $pubKeyHash;
    public string $xPub;
    public string $path;
}
