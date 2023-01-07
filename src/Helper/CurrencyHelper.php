<?php
declare(strict_types=1);

namespace App\CryptoGatewayEngine\Helper;

use App\CryptoGatewayEngine\Exception\PaymentGatewayException;
use App\CryptoGatewayEngine\Entity\{Currency, CurrencyNetwork};
use App\CryptoGatewayEngine\Enum\{CryptoCurrency, TokenType};
use Illuminate\Support\Arr;

class CurrencyHelper
{
    public function findCurrencyByContactAddress(string $contractAddress, bool $isTestnet, array $contracts): ?Currency
    {
        $configData = array_change_key_case($contracts, CASE_LOWER);
        $config = Arr::get($configData, strtolower($contractAddress));
        if (!$config || $isTestnet !== Arr::get($config, 'is_test')) {
            return null;
        }

        return new Currency(
            CryptoCurrency::fromValue(Arr::get($config, 'currency')),
            new CurrencyNetwork(
                TokenType::fromValue(Arr::get($config, 'network')),
                Arr::get($config, 'decimals'),
                $contractAddress
            )
        );
    }

    /**
     * @param string $code
     * @param array $config
     * @return Currency
     * @throws PaymentGatewayException
     */
    public function getCurrencyByCode(string $code, array $config): Currency
    {
        $concrete = $config[$code] ?? null;
        if (!$concrete) {
            throw new PaymentGatewayException('Currency not defined');
        }

        return new Currency(
            CryptoCurrency::fromValue($code),
            new CurrencyNetwork(TokenType::DEFAULT(), Arr::get($concrete, 'decimals'))
        );
    }

    /**
     * Define currency by code and network
     *
     * @param string $code
     * @param string|null $network
     * @param array $cryptoConfig
     * @return Currency
     */
    public function getCurrencyByCodeAndNetwork(string $code, ?string $network = null, array $cryptoConfig = []): Currency
    {
        $code = strtolower($code);
        $contract = $this->getContract($code, $network, $cryptoConfig);

        return new Currency(
            CryptoCurrency::fromValue($code),
            new CurrencyNetwork(
                TokenType::fromValue(!$network ? TokenType::DEFAULT() : $network),
                Arr::get($cryptoConfig, $code . '.decimals', Arr::get($contract, 'decimals')),
                Arr::get($contract, 'contract', '')
            )
        );
    }

    /**
     * Get erc20/trc20 contract params
     *
     * @param string $code
     * @param string|null $network
     * @param array $cryptoConfig
     * @return array
     */
    protected function getContract(string $code, ?string $network = null, array $cryptoConfig = []): array
    {
        $contracts = $cryptoConfig['contracts'] ?? [];
        if (!$network || empty($contracts)) {
            return [];
        }
        $fakeCurrency = new Currency(
            CryptoCurrency::fromValue($code),
            new CurrencyNetwork(TokenType::fromValue($network), 0)
        );
        $isTestNet = $cryptoConfig[strtolower($fakeCurrency->getNode())]['testnet'] ?? true;
        foreach ($contracts as $contract => $config) {
            if ($config['currency'] === $code
                && $config['network'] === $network
                && $isTestNet === $config['is_test']) {
                return array_merge($config, ['contract' => $contract]);
            }
        }

        return [];
    }
}
