<?php
declare(strict_types=1);

namespace App\CryptoGatewayEngine\Dto;

use App\CryptoGatewayEngine\Entity\CryptoCurrencyCoin;
use Spatie\DataTransferObject\DataTransferObject;

class GeneratorParamsDto extends DataTransferObject
{
    public string $coin;
    public string $mnemonic;
    public string $mnemonicPassword = '';
    public string $path;
    public CryptoCurrencyCoin $realCoin;
}
