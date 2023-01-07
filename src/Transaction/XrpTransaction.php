<?php
declare(strict_types=1);

namespace App\CryptoGatewayEngine\Transaction;

use App\CryptoGatewayEngine\Api\{AbstractApi, XrpApi};
use App\CryptoGatewayEngine\Dto\DestinationAddressDto;
use App\CryptoGatewayEngine\Exception\PaymentGatewayException;
use App\CryptoGatewayEngine\Entity\{Currency, Transaction, Wallet};
use App\CryptoGatewayEngine\Helper\Converter;
use Lessmore92\Buffer\Buffer;
use Lessmore92\RippleBinaryCodec\RippleBinaryCodec;
use Lessmore92\RippleKeypairs\RippleKeyPairs;

class XrpTransaction extends AbstractTransaction
{
    /**
     * @var AbstractApi | XrpApi
     */
    protected AbstractApi $api;

    private RippleKeyPairs $keypair;
    private RippleBinaryCodec $binaryCodec;

    public function __construct(Currency $currency, Wallet $wallet, AbstractApi $api, array $config = [])
    {
        parent::__construct($currency, $wallet, $api, $config);

        $this->keypair = new RippleKeyPairs();
        $this->binaryCodec = new RippleBinaryCodec();
        $this->feeSatoshi = (int)Converter::valueToCoin($this->api->getFee(), $this->wallet->getDecimals());
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
            throw new PaymentGatewayException('For XRP destinationAddresses array must contain only 1 element');
        }
        if ($extractFee) {
            throw new PaymentGatewayException('ExtractFee not implemented for XRP transaction');
        }
        $destinationAddress = array_shift($destinationAddresses);
        if (!($destinationAddress instanceof DestinationAddressDto)) {
            throw new PaymentGatewayException('destinationAddresses must be instanceof DestinationAddressDto');
        }
        // First payment for address must be greater than 10 xrp
        // @see https://xrpl.org/reserves.html
        if (!$this->api->isAddressExist($destinationAddress->address) && $destinationAddress->amount < 10){
            throw new PaymentGatewayException(sprintf('%s address not found.', $destinationAddress->address));
        }

        $transaction = [
            'TransactionType' => 'Payment',
            'Account'         => $this->wallet->getAddress(),
            'Fee'             => $this->feeSatoshi,
            'Destination'     => $destinationAddress->address,
            'Amount'          => (int)Converter::valueToCoin($destinationAddress->amount, $this->wallet->getDecimals()),
            'Sequence'        => $this->api->getSequence($this->wallet->getAddress()),
            'SigningPubKey'   => $this->wallet->getPublicKey()
        ];

        if ($destinationAddress->tag) {
            $tag = (int)$destinationAddress->tag;
            if ($destinationAddress->tag !== (string)$tag) {
                throw new PaymentGatewayException('Wrong destination tag format for XRP');
            }

            $transaction['DestinationTag'] = $tag;
        }

        return $this->sign($transaction);
    }

    /**
     * @throws \Exception
     */
    protected function sign(array $data): Transaction
    {
        $data['TxnSignature'] = $this->keypair->sign(
            Buffer::hex($this->binaryCodec->encodeForSigning($data))->getBinary(),
            '00' . $this->wallet->getPrivateKey()
        );
        $serialized = $this->binaryCodec->encode($data);

        return new Transaction($this->computeBinaryTransactionHash($serialized), $serialized, $this->feeSatoshi);
    }

    /**
     * Hash prefix is 0x54584e00
     * @see https://xrpl.org/basic-data-types.html
     * @throws \Exception
     */
    protected function computeBinaryTransactionHash(string $serializedTx): string
    {
        $prefix = Buffer::int(0x54584e00)->getHex();

        return Buffer::hex(hash('sha512', Buffer::hex($prefix . $serializedTx)
            ->getBinary()))
            ->slice(0, 32)
            ->getHex();
    }
}
