<?php
declare(strict_types=1);

namespace App\CryptoGatewayEngine\Monitor;

use App\CryptoGatewayEngine\Api\AbstractApi;
use App\CryptoGatewayEngine\Dto\ReceivedTransactionDto;
use App\CryptoGatewayEngine\Entity\Currency;
use App\CryptoGatewayEngine\Exception\PaymentGatewayException;
use App\CryptoGatewayEngine\Monitor\BlockStorage\BlockStorageContractInterface;

abstract class AbstractMonitor
{
    protected int $confirmations;
    protected AbstractApi $api;
    protected BlockStorageContractInterface $blockStorage;
    protected int $lastBlockId = 0;
    protected int $currentBlockId = 0;
    protected array $config = [];

    public static function instance(Currency $currency, AbstractApi $api, int $confirmations, array $config = []): self
    {
        $monitor = $config['concrete']['monitor'][$currency->getNode()] ?? null;
        if (!$monitor) {
            throw new PaymentGatewayException('Not found concrete implementation for ' . $currency->getNode() . ' monitor');
        }

        return app($monitor,
            [
                'currency'      => $currency,
                'api'           => $api,
                'confirmations' => $confirmations,
                'config' => $config
            ]
        );
    }

    public function __construct(AbstractApi $api, BlockStorageContractInterface $blockStorage, int $confirmations, array $config = [])
    {
        $this->api = $api;
        $this->confirmations = $confirmations;
        $this->blockStorage = $blockStorage;
        $this->config = $config;

        if ($this->getCustomBlockStart()) {
            $this->setLastBlockId($this->getCustomBlockStart());
        }
    }

    /**
     * Method to collect all new transactions for provided addresses
     *
     * @param callable(ReceivedTransactionDto):void $callback
     * @param array $addresses
     */
    public abstract function collectTransactions(callable $callback, array $addresses): void;

    /**
     * Method to check number of confirmations for provided hashes
     *
     * @param callable(string $hash, int $totalConfirmations):void $callback
     * @param array $hashes
     */
    public abstract function monitorConfirmations(callable $callback, array $hashes): void;

    /**
     * Set last scanned block, also info store to cache.
     *
     * @param int $lastScanned
     */
    protected function setLastBlockId(int $lastScanned): void
    {
        $this->blockStorage->setBlock($this->getKeyCache(), $lastScanned, $this->getMonitorTtlCache());
        $this->lastBlockId = $lastScanned;
    }

    /**
     * Get last scanned block id.
     * For the first time set to current block id
     *
     * @return int
     */
    public function getLastBlockId(): int
    {
        if (!$this->lastBlockId) {
            $this->lastBlockId = $this->blockStorage->getBlock($this->getKeyCache()) ?? 0;
        }
        if (!$this->lastBlockId) {
            $this->setLastBlockId($this->getCurrentBlockId());
        }
        return $this->lastBlockId;
    }

    /**
     * Get string key cache for last scanned block id
     *
     * @return string
     */
    protected abstract function getKeyCache(): string;

    /**
     * Get second ttl for cache
     *
     * @return int
     */
    protected abstract function getMonitorTtlCache(): int;

    /**
     * Get block id after which we must start scanning.
     * It used just for testing or if we need rescan blockchain.
     * Default 0
     *
     * @return int
     */
    protected abstract function getCustomBlockStart(): int;

    /**
     * Get maximal block id of blockchain
     *
     * @return int
     */
    protected abstract function getCurrentBlockId(): int;

    public function isTestnet(): bool
    {
        return $this->api->isTestnet();
    }

    public function neededConfirmations(): int
    {
        return $this->confirmations;
    }

    public function rollbackLastBlockId(): void
    {
        $lastSavedBlock = $this->blockStorage->getBlock($this->getKeyCache()) ?? 0;

        if (!$lastSavedBlock) {
            return;
        }

        $this->setLastBlockId($lastSavedBlock - 1);
    }
}
