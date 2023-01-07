<?php
declare(strict_types=1);

namespace App\CryptoGatewayEngine\Transaction\Combine;

use App\CryptoGatewayEngine\Api\{AbstractApi, Combine\NownodesApi};
use App\CryptoGatewayEngine\Exception\Bitcoin\NotEnoughAmountException;
use App\CryptoGatewayEngine\Exception\PaymentGatewayException;
use App\CryptoGatewayEngine\Exception\Bitcoin\UtxoIsNotConfirmedException;
use App\CryptoGatewayEngine\Transaction\AbstractTransaction;
use BitWasp\Bitcoin\Network\NetworkInterface;
use App\CryptoGatewayEngine\Dto\{DestinationAddressDto, UtxOutputDto};
use App\CryptoGatewayEngine\Entity\{AddressUtxo, Currency, Transaction, Wallet};
use App\CryptoGatewayEngine\Helper\Converter;
use BitWasp\Bitcoin\Address\AddressCreator;
use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Key\Factory\PrivateKeyFactory;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Transaction\{Factory\Signer, TransactionFactory, TransactionInterface, TransactionOutput};

abstract class BitcoinFamilyTransaction extends AbstractTransaction
{
    private int $feeSatoshiPerByte;
    private int $maxFeeSatoshi;
    private int $balanceInUtxo = 0;
    private int $needToTransfer = 0;

    protected array $destinationAddresses = [];
    protected float $totalTransferAmount = 0;
    protected array $addressUtxo = [];

    /**
     * @var AbstractApi|NownodesApi
     */
    protected AbstractApi $api;

    public function __construct(Currency $currency, Wallet $wallet, AbstractApi $api, array $config = [])
    {
        parent::__construct($currency, $wallet, $api, $config);
        Bitcoin::setNetwork($this->getNetworkFactory($this->api->isTestnet()));

        $this->feeSatoshiPerByte = (int) ($config[$currency->getNode()]['fee_satoshi_per_byte'] ?? 3);
        $this->maxFeeSatoshi = (int) ($config[$currency->getNode()]['max_fee_satoshi'] ?? 5000);
    }

    protected abstract function getNetworkFactory(bool $isTest = false): NetworkInterface;

    /**
     * @param DestinationAddressDto[] $destinationAddresses
     * @param Wallet[] $senderWallets
     * @param bool $extractFee - if pass multiple destinationAddresses fee will be extracted from the first one
     * @return Transaction
     * @throws PaymentGatewayException
     * @throws \BitWasp\Bitcoin\Exceptions\UnrecognizedAddressException
     */
    public function create(array $destinationAddresses, array $senderWallets = [], bool $extractFee = false): Transaction
    {
        $this->balanceInUtxo = 0;
        if (count($destinationAddresses) === 0) {
            throw new PaymentGatewayException('Minimum must be 1 receiver');
        }
        $this->extractFee = $extractFee;

        foreach ($destinationAddresses as $destinationAddress) {
            if (!($destinationAddress instanceof DestinationAddressDto)) {
                throw new PaymentGatewayException('destinationAddresses must be instanceof DestinationAddressDto');
            }
            if (!$this->api->isValidAddress($destinationAddress->address)){
                throw new PaymentGatewayException(sprintf('%s address is not valid.', $destinationAddress->address));
            }
        }

        $this->destinationAddresses = array_values($destinationAddresses);

        $this->totalTransferAmount = $this->totalTransferAmount();
        $transferAmount = Converter::valueToCoin($this->totalTransferAmount, $this->wallet->getDecimals());
        $this->needToTransfer = (int)$transferAmount;
        if (!$senderWallets) {
            $senderWallets = [$this->wallet];
        }
        $utxos = $this->recursionUtxo($senderWallets, (int)$transferAmount, false, $this->onlyConfirmedTx());

        if ($this->extractFee) {
            $transferAmount -= $this->feeSatoshi;
        }

        if ($this->balanceInUtxo < ($this->feeSatoshi + (int)$transferAmount)) {
            if ($this->onlyConfirmedTx()) {
                $transferAmount = Converter::valueToCoin($this->totalTransferAmount, $this->wallet->getDecimals());
                $this->feeSatoshi = 0;
                $this->recursionUtxo($senderWallets, (int)$transferAmount, false, false);
                if ($this->extractFee) {
                    $transferAmount -= $this->feeSatoshi;
                }
                if ($this->balanceInUtxo >= ($this->feeSatoshi + (int)$transferAmount)) {
                    throw new UtxoIsNotConfirmedException(
                        sprintf(
                            'Utxos is unconfirmed. Balance in utxos %s satoshi, needed %s satoshi.',
                            $this->balanceInUtxo,
                            $this->feeSatoshi + (int)$transferAmount
                        )
                    );
                }
            }
            throw new NotEnoughAmountException(
                $this->feeSatoshi,
                (int)$transferAmount,
                $this->balanceInUtxo,
                'Not enough coins for transaction.'
            );
        }

        $transaction = $this->buildTransaction($utxos);

        return $this->sign($transaction, $utxos);
    }

    protected function recursionUtxo(
        array $senderWallets,
        int $transferAmount,
        bool $considerFee = false,
        bool $onlyConfirmedTx = true
    ): array
    {
        $old = $transferAmount + $this->feeSatoshi;
        $utxos = $this->summarizeUtxo($senderWallets, $considerFee, $onlyConfirmedTx);
        try {
            $this->feeSatoshi = $this->calculateFee($utxos);
        } catch (PaymentGatewayException $exception) {
            $this->feeSatoshi = 0;
        }
        $this->needToTransfer = $transferAmount + $this->feeSatoshi;
        if (abs($old - $this->needToTransfer) > (4 * $this->feeSatoshiPerByte)
            && !$this->extractFee
            && $this->balanceInUtxo <= $this->needToTransfer) {

            return $this->recursionUtxo($senderWallets, $transferAmount, true, $onlyConfirmedTx);
        }

        return $utxos;
    }

    protected function summarizeUtxo(array $senderWallets, bool $considerFee = false, bool $onlyConfirmedTx = true): array
    {
        $this->balanceInUtxo = 0;
        $utxos = [];

        foreach ($senderWallets as $wallet) {
            if (!($wallet instanceof Wallet)) {
                throw new PaymentGatewayException('senderWallet must be instanceof Wallet');
            }

            $key = $wallet->getAddress() . '_' . $onlyConfirmedTx;
            if (!isset($this->addressUtxo[$key])) {
                $this->addressUtxo[$key] = $this->api->addressData($wallet, $onlyConfirmedTx);
            }

            $utxos = array_merge($utxos, $this->takeUtxo($this->addressUtxo[$key]));
            if ($considerFee) {
                $feeSatoshi = $this->calculateFee($utxos);
                if ($this->extractFee) {
                    $this->needToTransfer += $feeSatoshi - $this->feeSatoshi;
                }
                $this->feeSatoshi = $feeSatoshi;
            }
        }

        return $utxos;
    }

    /**
     * @param AddressUtxo $addressUtxo
     * @return UtxOutputDto[]
     * @throws \Exception
     */
    private function takeUtxo(AddressUtxo $addressUtxo): array
    {
        $utxos = $addressUtxo->suitableOutputs($this->needToTransfer);

        foreach ($utxos as $utxo) {
            $this->needToTransfer -= $utxo->value;
            $this->balanceInUtxo += $utxo->value;
        }

        return $utxos;
    }

    /**
     * @param UtxOutputDto[] $utxos
     * @throws \BitWasp\Bitcoin\Exceptions\UnrecognizedAddressException|PaymentGatewayException
     */
    private function calculateFee(array $utxos): int
    {
        $transaction = $this->buildTransaction($utxos);
        $preliminaryTx = $this->sign($transaction, $utxos);

        $feeSatoshi = (int)ceil($preliminaryTx->getSize() * $this->feeSatoshiPerByte);
        if ($feeSatoshi > $this->maxFeeSatoshi) {
            $feeSatoshi = $this->maxFeeSatoshi;
        }

        return $feeSatoshi;
    }

    /**
     * @param UtxOutputDto[] $utxos
     * @return TransactionInterface
     * @throws \BitWasp\Bitcoin\Exceptions\UnrecognizedAddressException|PaymentGatewayException
     */
    private function buildTransaction(array $utxos): TransactionInterface
    {
        $transferAmount = Converter::valueToCoin($this->totalTransferAmount, $this->wallet->getDecimals(), 0);
        if (!$utxos) {
            throw new PaymentGatewayException('Not enough coins for transaction.');
        }

        $transaction = TransactionFactory::build();
        $addressCreator = new AddressCreator();

        $totalInputAmount = 0;
        foreach ($utxos as $utxo) {
            $transaction->input($utxo->hash, $utxo->index);
            $totalInputAmount += $utxo->value;
        }

        /** @var DestinationAddressDto $destinationAddress */
        foreach ($this->destinationAddresses as $key => $destinationAddress) {
            $amount = Converter::valueToCoin($destinationAddress->amount, $this->wallet->getDecimals());
            if ($this->extractFee && $key === 0) {
                $amount -= $this->feeSatoshi;
                if ($amount <= 0) {
                    throw new PaymentGatewayException('Amount to send must be greater than 0');
                }
            }
            $transaction->payToAddress((int)$amount, $addressCreator->fromString($destinationAddress->address));
        }
        if ($this->extractFee) {
            $transferAmount -= $this->feeSatoshi;
        }
        $changeAmount = $totalInputAmount - $transferAmount - $this->feeSatoshi;

        if ($changeAmount > 0) {
            $transaction->payToAddress((int)$changeAmount, $addressCreator->fromString($this->wallet->getAddress()));
        }

        return $transaction->get();
    }

    /**
     * @param TransactionInterface $transaction
     * @param array|UtxOutputDto[] $utxos
     * @return Transaction
     * @throws \Exception
     */
    private function sign(TransactionInterface $transaction, array $utxos): Transaction
    {
        $signer = new Signer($transaction);

        foreach ($utxos as $index => $utxo) {
            $privateKey = (new PrivateKeyFactory(true))->fromWif($utxo->wallet->getPrivateKey());

            $txOut = new TransactionOutput(
                $utxo->value,
                ScriptFactory::scriptPubKey()->payToPubKeyHash($privateKey->getPubKeyHash())
            );

            $input = $signer->input($index, $txOut);
            $input->sign($privateKey);

            if (!$input->verify()) {
                throw new PaymentGatewayException('Wrong transaction signed input ' . $utxo->hash);
            }
        }

        $signed = $signer->get();

        return new Transaction($signed->getTxId()->getHex(), $signed->getHex(), $this->feeSatoshi);
    }

    protected function totalTransferAmount(): float
    {
        $amount = 0;
        foreach ($this->destinationAddresses as $destinationAddress) {
            $amount += $destinationAddress->amount;
        }

        return $amount;
    }

    protected function onlyConfirmedTx(): bool
    {
        return (bool)($this->config[$this->currency->getCode()]['only_confirmed_tx'] ?? true);
    }
}
