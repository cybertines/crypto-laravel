<?php
declare(strict_types=1);

namespace App\CryptoGatewayEngine\Api;

use App\CryptoGatewayEngine\Api\Request\ApiRequester;
use App\CryptoGatewayEngine\Dto\BlockInfoDto;
use App\CryptoGatewayEngine\Exception\PaymentGatewayException;
use App\CryptoGatewayEngine\Entity\{BroadcastResponse, Currency, Transaction};

abstract class AbstractApi
{
    protected bool $testnet;
    protected array $config = [];
    protected ApiRequester $client;

    private Currency $currency;

    public function __construct(Currency $currency, ApiRequester $client, bool $testnet = false, array $config = [])
    {
        $this->currency = $currency;
        $this->client = $client;
        $this->testnet = $testnet;
        $this->config = $config;
    }

    protected function getCurrency(): Currency
    {
        return $this->currency;
    }

    public function getCurrencyDecimals(): int
    {
        return $this->currency->getDecimals();
    }

    public function isTestnet(): bool
    {
        return $this->testnet;
    }

    /**
     * @throws \Exception
     */
    public static function instance(Currency $currency, ApiRequester $apiRequester, bool $testnet = false, array $config = []): self
    {
        $api = $config['concrete']['api'][$currency->getNode()] ?? null;
        if (!$api) {
            throw new PaymentGatewayException('Not found concrete implementation for ' . $currency->getNode() . ' api');
        }

        return new $api($currency, $apiRequester, $testnet, $config);
    }

    public abstract function pushTransaction(Transaction $transaction): BroadcastResponse;

    /**
     * @param string $address
     * @return mixed
     */
    public abstract function addressBalance(string $address): float;

    /**
     * Get fee transaction from blockchain by hash
     * @param string $hash
     * @return float
     *
     * @throws PaymentGatewayException
     */
    public abstract function getTransactionFeeByHash(string $hash): float;

    /**
     * Get block id by hash where first seen transaction
     *
     * @param string $hash
     * @return int
     *
     * @throws PaymentGatewayException
     */
    public abstract function getBlockIdByHash(string $hash): int;

    public abstract function getCurrentBlockInfo(): BlockInfoDto;

    public abstract function getBlockInfoByBlockId(int $blockId): BlockInfoDto;
}
