<?php
declare(strict_types=1);

namespace App\CryptoGatewayEngine\Monitor;

use App\CryptoGatewayEngine\Api\{AbstractApi, TrxApi};
use App\CryptoGatewayEngine\Monitor\BlockStorage\BlockStorageContractInterface;
use App\CryptoGatewayEngine\Dto\{ReceivedTransactionDto, TrxPartitionDataDto};
use Illuminate\Support\Arr;

class TrxMonitor extends AbstractMonitor
{
    /**
     * @var AbstractApi|TrxApi
     */
    protected AbstractApi $api;
    private array $userAddresses = [];

    public function __construct(AbstractApi $api, BlockStorageContractInterface $blockStorage, int $confirmations, array $config = [])
    {
        parent::__construct($api, $blockStorage, $confirmations, $config);

        $this->currentBlockId = $this->api->getCurrentBlock();
    }

    private function getUserAddress(array $addresses): array
    {
        if (!$this->userAddresses) {
            $this->userAddresses = array_map('strtoupper', $addresses);
        }
        return $this->userAddresses;
    }

    public function collectTransactions(callable $callback, array $addresses): void
    {
        $blockHashes = $this->pullBlocks();
        if (!$blockHashes->data) {
            return;
        }

        $userAddresses = $this->getUserAddress($addresses);

        foreach ($blockHashes->data as $blockHash) {
            if (!isset($blockHash['transactions'])) {
                continue;
            }

            $scannedBlock = (int)Arr::get($blockHash, 'block_header.raw_data.number');
            foreach ($blockHash['transactions'] as $transaction) {
                if (Arr::get($transaction, 'ret.0.contractRet') !== 'SUCCESS') {
                    continue;
                }

                $value = Arr::get($transaction, 'raw_data.contract.0.parameter.value');

                //If we have "amount" it means that it is trx transaction, otherwise contract transaction
                if (isset($value['amount'])) {
                    $address = $this->api->hexString2Address($value['to_address']);
                    $amount = (float)$value['amount'];
                } else {
                    //For contract transaction we must have "data"
                    if (!isset($value['data'])) {
                        continue;
                    }

                    //We scan only trc20 tokens
                    $parsedData = $this->api->parseToken($value['data']);
                    if (!$parsedData) {
                        continue;
                    }

                    $address = $parsedData->address;
                    $amount = $parsedData->value;
                }

                if (!in_array(strtoupper($address), $userAddresses)) {
                    continue;
                }

                $this->buildTransaction($callback, $transaction, $address, $amount, $scannedBlock);
            }
        }

        if ($blockHashes->partition > 0) {
            $this->collectTransactions($callback, $addresses);
        }
    }

    protected function buildTransaction(callable $callback, array $transaction, string $address, float $amount, int $blockId): void
    {
        $value = Arr::get($transaction, 'raw_data.contract.0.parameter.value');
        if (!isset($value['amount'])) {
            $contractAddress = $this->api->hexString2Address($value['contract_address']);
        }

        $currentConfirmations = $this->getCurrentBlockId() - $blockId;

        $dto = new ReceivedTransactionDto([
            'hash'            => $transaction['txID'],
            'amountInCoins'   => $amount,
            'toAddress'       => $address,
            'contractAddress' => $contractAddress ?? '',
            'isConfirmed'     => $currentConfirmations >= $this->confirmations,
            'confirmations'   => $currentConfirmations,
            'blockId'         => $blockId
        ]);

        $callback($dto);
    }

    protected function pullBlocks(): TrxPartitionDataDto
    {
        $id = $this->getLastBlockId();

        $notScannedBlocks = $this->getCurrentBlockId() - $id;
        if ($notScannedBlocks === 0) {
            return new TrxPartitionDataDto(['partition' => 0, 'data' => []]);
        }

        $numberOfPartition = ceil($notScannedBlocks / 50);

        if ($numberOfPartition > 1) {
            $data = $this->api->getBlockRange($id, $id + 49);
            $this->setLastBlockId($id + 50);
        } else {
            $data = $this->api->getBlockRange($id, $this->getCurrentBlockId());
            $this->setLastBlockId($this->getCurrentBlockId());
        }

        return new TrxPartitionDataDto(['partition' => (int)$numberOfPartition - 1, 'data' => $data]);
    }

    protected function getCurrentBlockId(): int
    {
        if (!$this->currentBlockId) {
            $this->currentBlockId = $this->api->getCurrentBlock();
        }

        return $this->currentBlockId;
    }

    public function monitorConfirmations(callable $callback, array $hashes): void
    {
        foreach ($hashes as $hash) {
            $info = $this->api->getTransactionInfo($hash);

            $currentConfirms = ($this->getCurrentBlockId() - ($info['blockNumber'] ?? $this->getCurrentBlockId())) + 1;
            if ($currentConfirms >= $this->confirmations) {
                $callback($hash, $currentConfirms);
            }
        }
    }

    protected function getCustomBlockStart(): int
    {
        return (int) ($this->config['trx']['custom_start_block'] ?? 0);
    }

    protected function getMonitorTtlCache(): int
    {
        return (int) ($this->config['trx']['monitor_ttl_cache'] ?? 3600);
    }

    protected function getKeyCache(): string
    {
        return 'last_trx_block';
    }
}
