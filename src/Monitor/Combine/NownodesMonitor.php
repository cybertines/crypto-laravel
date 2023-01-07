<?php
declare(strict_types=1);

namespace App\CryptoGatewayEngine\Monitor\Combine;

use App\CryptoGatewayEngine\Api\{AbstractApi, Combine\NownodesApi};
use App\CryptoGatewayEngine\Dto\ReceivedTransactionDto;
use App\CryptoGatewayEngine\Helper\Converter;
use App\CryptoGatewayEngine\Monitor\AbstractMonitor;
use Illuminate\Support\Arr;

abstract class NownodesMonitor extends AbstractMonitor
{
    /**
     * @var AbstractApi|NownodesApi
     */
    protected AbstractApi $api;
    private array $userAddresses = [];

    public function collectTransactions(callable $callback, array $addresses): void
    {
        $currentBlock = $this->getCurrentBlockId();
        if ($currentBlock === $this->getLastBlockId()) {
            return;
        }

        $this->userAddresses = array_map('strtoupper', $addresses);

        for ($i = $this->lastBlockId + 1; $i <= $currentBlock; $i++) {
            $hash = $this->api->getBlockHash($i);

            $block = $this->api->getBlockByHash($hash);

            if (!isset($block['tx'])) {
                continue;
            }

            $currentConfirmations = (int)Arr::get($block, 'confirmations', 0);

            foreach ($block['tx'] as $transaction) {
                $this->parseTransactions($callback, $transaction, $currentConfirmations, $i);
            }

            $this->setLastBlockId($i);
        }
    }

    protected function parseTransactions(callable $callback, array $transaction, int $currentConfirmations, int $blockId)
    {
        foreach ($transaction['vout'] as $tx) {
            $address = Arr::get($tx, 'scriptPubKey.address') ?? Arr::get($tx, 'scriptPubKey.addresses.0');
            if (!$address) {
                continue;
            }

            if (!in_array(strtoupper($address), $this->userAddresses)) {
                continue;
            }

            $amount = Converter::valueToCoin((float)Arr::get($tx, 'value'), $this->api->getCurrencyDecimals());

            $dto = new ReceivedTransactionDto([
                'hash'            => $transaction['txid'],
                'amountInCoins'   => (float)$amount,
                'toAddress'       => $address,
                'contractAddress' => '',
                'isConfirmed'     => $currentConfirmations >= $this->confirmations,
                'confirmations'   => $currentConfirmations,
                'blockId'         => $blockId
            ]);

            $callback($dto);
        }
    }

    public function monitorConfirmations(callable $callback, array $hashes): void
    {
        foreach ($hashes as $hash) {
            $info = $this->api->getRawTransaction($hash);
            $currentConfirms = (int)Arr::get($info, 'confirmations', 0);

            if ($currentConfirms >= $this->confirmations) {
                $callback($hash, $currentConfirms);
            }
        }
    }

    protected function getCurrentBlockId(): int
    {
        if (!$this->currentBlockId) {
            $info = $this->api->getBlockchainInfo();
            $this->currentBlockId = (int)Arr::get($info, 'blocks', 0);
        }

        return $this->currentBlockId;
    }
}
