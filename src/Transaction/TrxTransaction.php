<?php
declare(strict_types=1);

namespace App\CryptoGatewayEngine\Transaction;

use App\CryptoGatewayEngine\Api\{AbstractApi, TrxApi};
use App\CryptoGatewayEngine\Dto\DestinationAddressDto;
use App\CryptoGatewayEngine\Entity\Transaction;
use App\CryptoGatewayEngine\Entity\Wallet;
use App\CryptoGatewayEngine\Exception\PaymentGatewayException;

class TrxTransaction extends AbstractTransaction
{
    /**
     * @var AbstractApi|TrxApi
     */
    protected AbstractApi $api;

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
            throw new PaymentGatewayException('For TRX destinationAddresses array must contain only 1 element');
        }
        if ($extractFee) {
            throw new PaymentGatewayException('ExtractFee not implemented for TRX transaction');
        }
        $destinationAddress = array_shift($destinationAddresses);
        if (!($destinationAddress instanceof DestinationAddressDto)) {
            throw new PaymentGatewayException('destinationAddresses must be instanceof DestinationAddressDto');
        }
        if (!$this->api->isValidAddress($destinationAddress->address)){
            throw new PaymentGatewayException(sprintf('%s address is not valid.', $destinationAddress->address));
        }

        $this->api->setPrivateKey($this->wallet->getPrivateKey());
        if ($this->wallet->isTrc20()) {
            $raw = $this->api->generateTxContract(
                $destinationAddress->address,
                (string)$destinationAddress->amount,
                $this->wallet->getAddress());
        } else {
            $raw = $this->api->generateTx(
                $destinationAddress->address,
                $destinationAddress->amount,
                $this->wallet->getAddress());
        }

        return new Transaction($raw['txID'], json_encode($raw), $this->feeSatoshi);
    }
}
