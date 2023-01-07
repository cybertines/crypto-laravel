<?php
declare(strict_types=1);

namespace App\CryptoGatewayEngine\Monitor;

use App\CryptoGatewayEngine\Api\{AbstractApi, XrpApi};
use App\CryptoGatewayEngine\Dto\ReceivedTransactionDto;
use App\CryptoGatewayEngine\Exception\PaymentGatewayException;
use App\CryptoGatewayEngine\Monitor\BlockStorage\BlockStorageContractInterface;

class XrpMonitor extends AbstractMonitor
{
    /**
     * @var AbstractApi|XrpApi
     */
    protected AbstractApi $api;
    private string $resultMessage;
    private array $userAddresses = [];

    public function __construct(AbstractApi $api, BlockStorageContractInterface $blockStorage, int $confirmations, array $config = [])
    {
        parent::__construct($api, $blockStorage, $confirmations, $config);

        $this->resultMessage = 'tesSUCCESS';
    }

    public function collectTransactions(callable $callback, array $addresses): void
    {
        $currentBlock = $this->getCurrentBlockId();
        if ($currentBlock === $this->getLastBlockId()) {
            return;
        }

        $this->userAddresses = array_map('strtoupper', $addresses);

        for ($i = $this->lastBlockId + 1; $i <= $currentBlock; $i++) {
            $res = $this->api->getBlockById($i);

            if (!isset($res['result']['ledger']['transactions'])) {
                continue;
            }

            foreach ($res['result']['ledger']['transactions'] as $transaction) {
                $this->parseTransactions($callback, $transaction, $currentBlock - $i, $i);
            }
            $this->setLastBlockId($i);
        }
    }

    protected function parseTransactions(callable $callback, array $transaction, int $currentConfirmations, int $blockId)
    {
        if (!isset($transaction['TransactionType']) || $transaction['TransactionType'] !== 'Payment') {
            return;
        }

        if (!isset($transaction['metaData']['TransactionResult'])
            || $transaction['metaData']['TransactionResult'] !== $this->resultMessage) {
            return;
        }

        $address = $transaction['Destination'] ?? null;

        if (!$address || !in_array(strtoupper($address), $this->userAddresses)) {
            return;
        }

        $amount = $transaction['Amount'] ?? 0;
        $destinationTag = $transaction['DestinationTag'] ?? null;

        $dto = new ReceivedTransactionDto([
            'hash'            => $transaction['hash'],
            'amountInCoins'   => (float)$amount,
            'toAddress'       => $address,
            'contractAddress' => '',
            'isConfirmed'     => $currentConfirmations >= $this->confirmations,
            'confirmations'   => $currentConfirmations,
            'blockId'         => $blockId,
            'tag' => $destinationTag ? (string)$destinationTag : null,
        ]);

        $callback($dto);
    }

    public function monitorConfirmations(callable $callback, array $hashes): void
    {
        $blockNumber = $this->getCurrentBlockId();

        foreach ($hashes as $hash) {
            $info = $this->api->getTransactionByHash($hash);
            $ledgerIndex = $info['result']['ledger_index'] ?? $blockNumber;
            $currentConfirms = ($blockNumber - $ledgerIndex) + 1;

            if ($currentConfirms >= $this->confirmations) {
                $callback($hash, $currentConfirms);
            }
        }
    }

    protected function getCurrentBlockId(): int
    {
        if (!$this->currentBlockId) {
            $currentBlockId = $this->api->getServerInfo()['result']['info']['validated_ledger']['seq'] ?? null;
            if (is_null($currentBlockId)) {
                throw new PaymentGatewayException('Could not get current block id from blockchain');
            }
            $this->currentBlockId = $currentBlockId;
        }

        return $this->currentBlockId;
    }

    protected function getCustomBlockStart(): int
    {
        return (int) ($this->config['xrp']['custom_start_block'] ?? 0);
    }

    protected function getMonitorTtlCache(): int
    {
        return (int) ($this->config['xrp']['monitor_ttl_cache'] ?? 300);
    }

    protected function getKeyCache(): string
    {
        return 'last_xrp_block';
    }
}
