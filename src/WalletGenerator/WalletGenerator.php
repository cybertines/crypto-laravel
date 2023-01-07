<?php
declare(strict_types=1);

namespace App\CryptoGatewayEngine\WalletGenerator;

use App\CryptoGatewayEngine\Exception\PaymentGatewayException;
use App\CryptoGatewayEngine\Entity\CryptoCurrencyCoin;
use BitWasp\Bitcoin\Mnemonic\MnemonicFactory;
use App\CryptoGatewayEngine\Dto\{GeneratorParamsDto, WalletDto};

class WalletGenerator
{
    protected function generateEth(string $mnemonic, int $pathNumber = 0, bool $isTest = false): WalletDto
    {
        return $this->generateAddress(
            $mnemonic,
            $this->getCoinByNetwork(CryptoCurrencyCoin::ETH, $isTest),
            $this->getFullPath($isTest ? 1 : 60, $pathNumber),
            CryptoCurrencyCoin::ETH
        );
    }

    protected function generateBtc(string $mnemonic, int $pathNumber = 0, bool $isTest = false): WalletDto
    {
        return $this->generateAddress(
            $mnemonic,
            $this->getCoinByNetwork(CryptoCurrencyCoin::BTC, $isTest),
            $this->getFullPath($isTest ? 1 : 0, $pathNumber),
            CryptoCurrencyCoin::BTC
        );
    }

    protected function generateLtc(string $mnemonic, int $pathNumber = 0, bool $isTest = false): WalletDto
    {
        return $this->generateAddress(
            $mnemonic,
            $this->getCoinByNetwork(CryptoCurrencyCoin::LTC, $isTest),
            $this->getFullPath($isTest ? 1 : 2, $pathNumber),
            CryptoCurrencyCoin::LTC
        );
    }

    protected function generateXrp(string $mnemonic, int $pathNumber = 0, bool $isTest = false): WalletDto
    {
        return $this->generateAddress(
            $mnemonic,
            CryptoCurrencyCoin::BTC,
            $this->getFullPath(144, $pathNumber),
            CryptoCurrencyCoin::XRP
        );
    }

    protected function generateTrx(string $mnemonic, int $pathNumber = 0, bool $isTest = false): WalletDto
    {
        return $this->generateAddress(
            $mnemonic,
            CryptoCurrencyCoin::BTC,
            $this->getFullPath(195, $pathNumber),
            CryptoCurrencyCoin::TRX
        );
    }

    protected function generateAddress($mnemonic, $coin, $path, $realCoin): WalletDto
    {
        $dto = new GeneratorParamsDto([
            "mnemonic" => $mnemonic,
            "coin"     => $coin,
            "path"     => $path,
            "realCoin" => new CryptoCurrencyCoin($realCoin)
        ]);

        return (new WalletDerive($dto))->generate();
    }

    protected function getCoinByNetwork(string $coin, bool $isTest): string
    {
        return !$isTest ? $coin : $coin . '-test';
    }

    protected function getFullPath(int $coin, int $pathNumber): string
    {
        
        return '44\'/' . $coin . '\'/0\'/0/' . $pathNumber;
//         return 'm/44\'/' . $coin . '\'/0\'/0/' . $pathNumber;
    }

    public function generate(CryptoCurrencyCoin $coin, string $mnemonic, int $pathNumber = 0, bool $isTest = false): WalletDto
    {
        if (!$this->isSeedValid($mnemonic)) {
            throw new PaymentGatewayException('Seed phrase is not valid');
        }
        if ($pathNumber < 0) {
            throw new PaymentGatewayException('Path number must be greater than or equal to zero');
        }
        return call_user_func([$this, 'generate' . ucfirst($coin->getValue())], $mnemonic, $pathNumber, $isTest);
    }

    public function generateSeed(): string
    {
        return MnemonicFactory::bip39()->create(256);
    }

    public function isSeedValid(string $seed): bool
    {
        try {
            MnemonicFactory::bip39()->mnemonicToEntropy($seed);
        } catch (\InvalidArgumentException $exception) {
            return false;
        }

        return true;
    }
}

