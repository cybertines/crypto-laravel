<?php
declare(strict_types=1);

namespace App\CryptoGatewayEngine\Dto;

use Spatie\DataTransferObject\DataTransferObject;

class TrxPartitionDataDto extends DataTransferObject
{
    public int $partition;
    public array $data;
}
