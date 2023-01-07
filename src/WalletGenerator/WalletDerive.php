<?php
declare(strict_types=1);

namespace App\CryptoGatewayEngine\WalletGenerator;

use App\CryptoGatewayEngine\Exception\PaymentGatewayException;
use App\CryptoGatewayEngine\Entity\CryptoCurrencyCoin;
use BitWasp\Bitcoin\Exceptions\{Base58ChecksumFailure, Base58InvalidCharacter, InvalidNetworkParameter};
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PrivateKeyInterface;
use BitWasp\Bitcoin\Key\KeyToScript\ScriptDataFactory;
use BitWasp\Bitcoin\Network\NetworkInterface;
use BitWasp\Buffertools\Exceptions\ParserOutOfRange;
use App\CryptoGatewayEngine\Dto\{CoinDto, GeneratorParamsDto, WalletDto};
use Exception;
use App\CryptoGatewayEngine\WalletGenerator\Util\{Coin\CoinParams,
    RippleHelper,
    TronHelper,
    EthereumHelper,
    FlexNetwork,
    Base58,
    MultiCoinRegistry
};
use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Key\Deterministic\HierarchicalKey;
use BitWasp\Bitcoin\Key\Deterministic\HdPrefix\{GlobalPrefixConfig, NetworkConfig, ScriptPrefix};
use BitWasp\Bitcoin\Key\Deterministic\Slip132\Slip132;
use BitWasp\Bitcoin\Key\Factory\HierarchicalKeyFactory;
use BitWasp\Bitcoin\Key\KeyToScript\KeyToScriptHelper;
use BitWasp\Bitcoin\Serializer\Key\HierarchicalKey\{Base58ExtendedKeySerializer,
    ExtendedKeySerializer,
    RawExtendedKeySerializer
};
use BitWasp\Bitcoin\Mnemonic\Bip39\Bip39SeedGenerator;
use BitWasp\Bitcoin\Address\AddressCreator;
use BitWasp\Buffertools\Parser;

class WalletDerive
{
    protected HierarchicalKeyFactory $hkf;
    protected GeneratorParamsDto $dto;

    public function __construct(GeneratorParamsDto $dto)
    {
        $this->dto = $dto;
        $this->hkf = new HierarchicalKeyFactory();
    }

    /**
     * @return WalletDto
     * @throws InvalidNetworkParameter
     * @throws Exception
     */
    public function generate(): WalletDto
    {
        $key = $this->mnemonicToKey();

        $network = new FlexNetwork($this->dto->coin);

        Bitcoin::setNetwork($network);
        $keyType = $this->getKeyTypeFromCoinAndKey($key);

        $master = $this->fromExtended($key, $network);

        return $this->deriveKeyWorker($network, $master->derivePath($this->dto->path), $keyType);
    }

    /**
     * @param NetworkInterface $network
     * @param HierarchicalKey $key
     * @param string $keyType
     * @return WalletDto
     * @throws Base58InvalidCharacter
     * @throws Exception
     */
    private function deriveKeyWorker(NetworkInterface $network, HierarchicalKey $key, string $keyType): WalletDto
    {
        $coinDto = $this->getSymbolAndNetwork();
        if (!$this->networkSupportsKeyType($keyType, $this->dto->coin)) {
            throw new PaymentGatewayException($keyType . 'extended keys are not supported for ' . $this->dto->coin);
        }

        if (method_exists($key, 'getPublicKey')) {
            $address = $this->address($key, $network);
            $prvWif = $key->isPrivate() ? $this->serializePrivateKey($coinDto->symbol, $network, $key->getPrivateKey()) : null;
            $pubKey = $key->getPublicKey()->getHex();

            if ($this->dto->realCoin->getValue() === CryptoCurrencyCoin::ETH) {
                $address = EthereumHelper::getFullEthereumAddress($key->getPublicKey());
                $pubKey = EthereumHelper::addEthSuffix($pubKey);
            }

            if ($this->dto->realCoin->getValue() === CryptoCurrencyCoin::XRP) {
                $address = RippleHelper::getAddress($address);
                $prvWif = RippleHelper::getPrivateKey($prvWif);
            }

            if ($this->dto->realCoin->getValue() === CryptoCurrencyCoin::TRX) {
                $addressHex = '41' . EthereumHelper::getEthereumAddress($key->getPublicKey());
                $address = TronHelper::getBase58CheckAddress(hex2bin($addressHex));
                $prvWif = $key->getPrivateKey()->getHex();
            }

            return new WalletDto([
                'address'    => $address,
                'xPrv'       => $key->isPrivate() ? $this->toExtendedKey($this->dto->coin, $key, $network) : null,
                'privateKey' => $prvWif,
                'pubKey'     => $pubKey,
                'pubKeyHash' => $key->getPublicKey()->getPubKeyHash()->getHex(),
                'xPub'       => $this->toExtendedKey($this->dto->coin, $key->withoutPrivateKey(), $network),
                'coin'       => $this->dto->realCoin->getValue(),
                'path'       => $this->dto->path
            ]);
        } else {
            throw new PaymentGatewayException('multisig keys not supported');
        }
    }

    private function serializePrivateKey(string $symbol, NetworkInterface $network, PrivateKeyInterface $key): string
    {
        return strtolower($symbol) === CryptoCurrencyCoin::ETH
            ? EthereumHelper::addEthSuffix($key->getHex())
            : $key->toWif($network);
    }

    private function address(HierarchicalKey $key, NetworkInterface $network): string
    {
        return $key->getAddress(new AddressCreator())->getAddress($network);
    }

    /**
     * @param string $key
     * @return mixed
     * @throws Base58ChecksumFailure
     * @throws Base58InvalidCharacter
     * @throws ParserOutOfRange
     */
    private function getKeyTypeFromCoinAndKey(string $key)
    {
        $prefix = substr($key, 0, 4);

        $s = new RawExtendedKeySerializer(Bitcoin::getEcAdapter());
        $rkp = $s->fromParser(new Parser(Base58::decodeCheck($key)));
        $keyPrefix = EthereumHelper::addEthSuffix($rkp->getPrefix());

        $ext = $this->getExtendedPrefixes($this->dto->coin);

        foreach ($ext as $kt => $info) {
            if (isset($info['public']) && $keyPrefix === strtolower($info['public'])) {
                return $kt[0];
            }
            if (isset($info['private']) && $keyPrefix === strtolower($info['private'])) {
                return $kt[0];
            }
        }
        throw new PaymentGatewayException('KeyType not found for ' . $this->dto->coin . '/' . $prefix);
    }

    /**
     * @param string $coin
     * @param NetworkInterface $network
     * @return Base58ExtendedKeySerializer
     * @throws InvalidNetworkParameter
     */
    private function getSerializer(string $coin, NetworkInterface $network): Base58ExtendedKeySerializer
    {
        $adapter = Bitcoin::getEcAdapter();

        $prefix = $this->getScriptPrefixForKeyType($coin);
        $config = new GlobalPrefixConfig([new NetworkConfig($network, [$prefix])]);

        return new Base58ExtendedKeySerializer(new ExtendedKeySerializer($adapter, $config));
    }

    private function getSymbolAndNetwork(?string $coin = null): CoinDto
    {
        if (!$coin) {
            $coin = $this->dto->coin;
        }
        list($symbol, $network) = explode('-', $this->coinToChain($coin));

        return new CoinDto([
            'symbol'  => $symbol,
            'network' => $network
        ]);
    }

    /**
     * @param string|null $coin
     * @return array
     * @throws Exception
     */
    private function getNetworkParams(?string $coin = null): array
    {
        $dto = $this->getSymbolAndNetwork($coin);
        return CoinParams::getCoinNetwork($dto->symbol, $dto->network);
    }

    /**
     * @param string $coin
     * @return array
     * @throws Exception
     */
    private function getExtendedPrefixes(string $coin): array
    {
        $params = $this->dto->toArray();
        $networkParams = $this->getNetworkParams($coin);

        if (isset($params['alt-extended'])) {
            $ext = $params['alt-extended'];
            $val = $networkParams['prefixes']['extended']['alternates'][$ext] ?? null;
            if (!$val) {
                throw new PaymentGatewayException('Invalid value for --alt-extended.  Check coin type');
            }
        } else {
            $val = $networkParams['prefixes']['extended'] ?? null;
            unset($val['alternates']);
        }
        $val = $val ?: [];

        foreach ($val as $k => $v) {
            if (!($v['public'] ?? null) || !($v['private'] ?? null)) {
                unset($val[$k]);
            }
        }
        return $val;
    }

    /**
     * @param string $keyType
     * @param string $coin
     * @return bool
     * @throws Exception
     */
    private function networkSupportsKeyType(string $keyType, string $coin): bool
    {
        $mcr = new MultiCoinRegistry($this->getExtendedPrefixes($coin));

        return (bool)$mcr->prefixBytesByKeyType($keyType);
    }

    private function getScriptDataFactoryForKeyType(): ScriptDataFactory
    {
        return (new KeyToScriptHelper(Bitcoin::getEcAdapter()))->getP2pkhFactory();
    }

    /**
     * @param string $coin
     * @return ScriptPrefix
     * @throws InvalidNetworkParameter
     * @throws Exception
     */
    private function getScriptPrefixForKeyType(string $coin): ScriptPrefix
    {
        $slip132 = new Slip132(new KeyToScriptHelper(Bitcoin::getEcAdapter()));
        $extPrefixes = $this->getExtendedPrefixes($coin);
        $coinPrefixes = new MultiCoinRegistry($extPrefixes);

        return $slip132->p2pkh($coinPrefixes);
    }

    /**
     * @param string $coin
     * @param HierarchicalKey $key
     * @param NetworkInterface $network
     * @return string
     * @throws Exception
     */
    private function toExtendedKey(string $coin, HierarchicalKey $key, NetworkInterface $network): string
    {
        $serializer = $this->getSerializer($coin, $network);
        return $serializer->serialize($network, $key);
    }

    /**
     * @param string $extendedKey
     * @param NetworkInterface $network
     * @return HierarchicalKey
     * @throws Base58ChecksumFailure
     * @throws ParserOutOfRange|InvalidNetworkParameter
     */
    private function fromExtended(string $extendedKey, NetworkInterface $network): HierarchicalKey
    {
        $serializer = $this->getSerializer($this->dto->coin, $network);
        return $serializer->parse($network, $extendedKey);
    }

    /**
     * @return string
     * @throws InvalidNetworkParameter
     * @throws Exception
     */
    private function mnemonicToKey(): string
    {
        $network = new FlexNetwork($this->dto->coin);
        Bitcoin::setNetwork($network);

        $seedGenerator = new Bip39SeedGenerator();

        $seed = $seedGenerator->getSeed($this->dto->mnemonic, $this->dto->mnemonicPassword);
        $scriptFactory = $this->getScriptDataFactoryForKeyType();
        $bip32 = $this->hkf->fromEntropy($seed, $scriptFactory);

        return $this->toExtendedKey($this->dto->coin, $bip32, $network);
    }

    private function coinToChain(string $coin): string
    {
        return strstr($coin, '-') ? $coin : $coin . '-main';
    }
}

