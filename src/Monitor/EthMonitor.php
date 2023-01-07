<?php
declare(strict_types=1);

namespace App\CryptoGatewayEngine\Monitor;

use App\CryptoGatewayEngine\Api\{AbstractApi, EthApi};
use App\CryptoGatewayEngine\Dto\ReceivedTransactionDto;
use App\CryptoGatewayEngine\Helper\Converter;

class EthMonitor extends AbstractMonitor
{
    /**
     * @var AbstractApi|EthApi
     */
    protected AbstractApi $api;

    public function collectTransactions(callable $callback, array $addresses): void
    {
        $currentBlock = $this->getCurrentBlockId();
        if ($currentBlock === $this->getLastBlockId()) {
            return;
        }

        $userAddresses = array_map('strtoupper', $addresses);

        for ($i = $this->lastBlockId + 1; $i <= $currentBlock; $i++) {
            $res = $this->api->getBlockById($i);

            if (!isset($res['transactions'])) {
                continue;
            }

            foreach ($res['transactions'] as $transaction) {
                $value = Converter::hexToDec($transaction['value']);

                //if true - means that it is eth transfer
                if ($value > 0) {
                    $address = $transaction['to'];
                    $amount = $value;
                } else {
                    //It is contract transfer/execution
                    $parsedData = $this->api->parseToken($transaction['input']);

                    //Only scan ERC20 contracts
                    if (!$parsedData) {
                        continue;
                    }

                    $address = $parsedData->address;
                    $amount = $parsedData->value;
                }

                if (is_null($address)) {
                    continue;
                }
                if (!in_array(strtoupper($address), $userAddresses)) {
                    continue;
                }

                $this->buildTransaction($callback, $transaction, $address, $amount, $i);
            }

            $this->setLastBlockId($i);
        }
    }

    protected function buildTransaction(callable $callback, array $transaction, string $address, float $amount, int $blockId): void
    {
        //0x0 - it means that 0 eth was sent
        if ($transaction['value'] === '0x0') {
            $contractAddress = $transaction['to'];
        }

        $currentConfirmations = $this->getCurrentBlockId() - $blockId;

        $dto = new ReceivedTransactionDto([
            'hash'            => $transaction['hash'],
            'amountInCoins'   => $amount,
            'toAddress'       => $address,
            'contractAddress' => $contractAddress ?? '',
            'isConfirmed'     => $currentConfirmations >= $this->confirmations,
            'confirmations'   => $currentConfirmations,
            'blockId'         => $blockId
        ]);

        $callback($dto);
    }

    public function monitorConfirmations(callable $callback, array $hashes): void
    {
        $blockNumber = $this->getCurrentBlockId();

        foreach ($hashes as $hash) {
            $info = $this->api->getTransactionByHash($hash);
            $currentConfirms = ($blockNumber - ($info['blockNumber'] ?? $blockNumber)) + 1;

            if ($currentConfirms >= $this->confirmations) {
                $callback($hash, (int)$currentConfirms);
            }
        }
    }

    protected function getCurrentBlockId(): int
    {
        if (!$this->currentBlockId) {
            $this->currentBlockId = $this->api->blockNumber();
        }

        return $this->currentBlockId;
    }

    protected function getCustomBlockStart(): int
    {
        return (int) ($this->config['eth']['custom_start_block'] ?? 0);
    }

    protected function getMonitorTtlCache(): int
    {
        return (int) ($this->config['eth']['monitor_ttl_cache'] ?? 3600);
    }

    protected function getKeyCache(): string
    {
        return 'last_eth_block';
    }
}
