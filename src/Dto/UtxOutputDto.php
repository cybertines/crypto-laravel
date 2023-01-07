<?php
declare(strict_types=1);

namespace App\CryptoGatewayEngine\Dto;

use App\CryptoGatewayEngine\Entity\Wallet;
use Spatie\DataTransferObject\DataTransferObject;

class UtxOutputDto extends DataTransferObject
{
    public string $hash = '';
    public int $index = 0;
    public int $value = 0;
    public Wallet $wallet;
}
