<?php
declare(strict_types=1);

namespace App\CryptoGatewayEngine\Transaction;

use App\CryptoGatewayEngine\Api\{AbstractApi, EthApi};
use App\CryptoGatewayEngine\Dto\DestinationAddressDto;
use App\CryptoGatewayEngine\Exception\PaymentGatewayException;
use App\CryptoGatewayEngine\Entity\{Currency, Transaction, Wallet};
use App\CryptoGatewayEngine\Helper\Converter;
use Web3p\EthereumTx\Transaction as Web3Transaction;

class EthTransaction extends AbstractTransaction
{
    private const MAINNET_CHAIN_ID = 1;
    private const ROPSTEN_CHAIN_ID = 3;

    private const TRANSFER_METHOD_SIGNATURE = '0xa9059cbb';
    /**
     * @var AbstractApi|EthApi
     */
    protected AbstractApi $api;
    private string $gas;
    private string $gasToken;

    public function __construct(Currency $currency, Wallet $wallet, AbstractApi $api, array $config = [])
    {
        parent::__construct($currency, $wallet, $api, $config);

        $this->gas = (string) ($config['eth']['gas'] ?? '0');
        $this->gasToken = (string) ($config['eth']['gas_token'] ?? '0');
    }

    /**
     * @param DestinationAddressDto[] $destinationAddresses
     * @param Wallet[] $senderWallets
     * @param bool $extractFee
     * @return Transaction
     * @throws PaymentGatewayException
     */
    public function create(array $destinationAddresses, array $senderWallets = [], bool $extractFee = false): Transaction
    {
        if (count($destinationAddresses) !== 1) {
            throw new PaymentGatewayException('For ETH destinationAddresses array must contain only 1 element');
        }
        if ($extractFee) {
            throw new PaymentGatewayException('ExtractFee not implemented for ETH transaction');
        }
        $destinationAddress = array_shift($destinationAddresses);
        if (!($destinationAddress instanceof DestinationAddressDto)) {
            throw new PaymentGatewayException('destinationAddresses must be instanceof DestinationAddressDto');
        }
        if (!$this->api->isValidAddress($destinationAddress->address)){
            throw new PaymentGatewayException(sprintf('%s address is not valid.', $destinationAddress->address));
        }

        $transaction = new Web3Transaction($this->getParams($destinationAddress));
        $signedTransaction = $transaction->sign($this->wallet->getPrivateKey());

        return new Transaction('', '0x' . $signedTransaction);
    }

    protected function getValue(float $amount): string
    {
        return Converter::valueToCoin($amount, $this->wallet->getDecimals());
    }

    protected function getParams(DestinationAddressDto $destinationAddress): array
    {
        $params = [
            'nonce'    => $this->nextNonce(),
            'chainId'  => $this->api->isTestnet() ? self::ROPSTEN_CHAIN_ID : self::MAINNET_CHAIN_ID,
            'from'     => $this->wallet->getAddress(),
            'gasPrice' => $this->api->getRawGasPrice(),
        ];

        $value = Converter::decToHex($this->getValue($destinationAddress->amount));

        if ($this->wallet->isErc20()) {
            $params['value'] = '0x0';
            $params['gas'] = Converter::decToHex($this->gasToken);
            $params['to'] = $this->wallet->getContractAddress();
            $params['data'] = $this->buildData($destinationAddress->address, $value);
        } else {
            $params['gas'] = Converter::decToHex($this->gas);
            $params['value'] = $value;
            $params['to'] = $destinationAddress->address;
        }

        return $params;
    }

    protected function buildData(string $address, string $hexValue): string
    {
        return self::TRANSFER_METHOD_SIGNATURE .
            Converter::leftPadZero(substr($address, 2)) .
            Converter::leftPadZero(substr($hexValue, 2));
    }

    /**
     * Transaction nonce for current address.
     *
     * If transaction is pending and you want to rewrite it, you must use the same nonce and set bigger gas fee.
     * If you want to get last nonce without pending transaction use tag "latest"
     * @return string
     */
    protected function nextNonce(): string
    {
        return $this->api->getTransactionCount($this->wallet->getAddress(), 'pending');
    }
}
