<?php
declare(strict_types=1);

namespace App\CryptoGatewayEngine\Tests\Crypto;

use App\CryptoGatewayEngine\Api\Request\ApiRequester;
use App\CryptoGatewayEngine\Api\Request\Logger\NullLogger;
use App\CryptoGatewayEngine\Dto\DestinationAddressDto;
use App\CryptoGatewayEngine\Entity\Currency;
use App\CryptoGatewayEngine\Entity\CurrencyNetwork;
use App\CryptoGatewayEngine\Entity\Wallet;
use App\CryptoGatewayEngine\Enum\CryptoCurrency;
use App\CryptoGatewayEngine\Enum\TokenType;
use App\CryptoGatewayEngine\Entity\CryptoCurrencyCoin;
use App\CryptoGatewayEngine\Exception\PaymentGatewayException;
use App\CryptoGatewayEngine\Factory\AbstractCryptoFactory;
use App\CryptoGatewayEngine\WalletGenerator\WalletGenerator;
use App\CryptoGatewayEngine\Tests\TestCase;
use GuzzleHttp\Client;

class BitcoinTest extends TestCase
{
    private string $seed = 'recall code question stone april large grab fatal aware wolf shine measure silly draw funny patch tilt cigar alter club index sister armed purse';

    public function testBroadcast(): void
    {
        $walletGenerator = new WalletGenerator();
        // $mnemonic = $walletGenerator->generateSeed();
        $mnemonic = 'bag grid surprise pyramid waste patch song genuine viable hurry home boss kiss buffalo cook quiz unaware arrest plunge agent good dream need typical';

        $coin = new CryptoCurrencyCoin(CryptoCurrencyCoin::BTC);
        $this->assertEquals('btc', $coin->getValue());

        $senderWalletDto = $walletGenerator->generate($coin, $mnemonic, 0, true);
        $this->assertEquals('mkn3BdqWdnASJBngVFsF5ruTDZt4rpCHCh', $senderWalletDto->address);
        $this->assertEquals('02a957a13d361bc070c716c5959ba5548f9c55c4f15f75f83e38fe1c799a86728f', $senderWalletDto->pubKey);
        $this->assertEquals('cQwdejgDf3eWDivazqb8x4Noava8XSiYuy5kerafWa31zL5nE9Ns', $senderWalletDto->privateKey);
        $this->assertEquals('tprv8jqkS9LaajzNn3nvaufvLxjKhdyGgf5Hixuppqseci1eM2wkfSCKWuQhb9bEzMuvW9k99D5W9Sis6qjKvtjxHvRBvv7q2zaDLhvunipEeDJ', $senderWalletDto->xPrv);
        $this->assertEquals('39b2f323ba0832313d78b4cc62c4f523f4fcd680', $senderWalletDto->pubKeyHash);
        $this->assertEquals('tpubDGXnaZNpj7g3fWpiUZLWkNPSGfVCqzGCJGWc7Mux2yp3BXCXHq1uhQ2ZmH4Z4Fnm2XXcCAyZuuTp6L6VHZod3DatBfK3vkCmMdtf4zTjQNo', $senderWalletDto->xPub);

        $currency = new Currency(CryptoCurrency::BTC(), new CurrencyNetwork(TokenType::DEFAULT(), 8));
        $this->assertEquals('btc', $currency->getCode());
        $this->assertEquals('default', $currency->getNetwork()->getTokenType());

        $senderWallet = new Wallet($senderWalletDto->address, $senderWalletDto->privateKey, $currency, $senderWalletDto->pubKey);
        $this->assertEquals('mkn3BdqWdnASJBngVFsF5ruTDZt4rpCHCh', $senderWallet->getAddress());
        $this->assertEquals('cQwdejgDf3eWDivazqb8x4Noava8XSiYuy5kerafWa31zL5nE9Ns', $senderWallet->getPrivateKey());
        $this->assertEquals('02a957a13d361bc070c716c5959ba5548f9c55c4f15f75f83e38fe1c799a86728f', $senderWallet->getPublicKey());

        $destinationAddressDto = new DestinationAddressDto([
            'address' => 'n2k8dJGvQa9Z4G5gPDogKPsopbvoGoJmaR',
            'amount' => 0.0000231
        ]);

        $apiRequester = new ApiRequester(new Client(), new NullLogger());
        $crypto = AbstractCryptoFactory::instanceByCurrency(
            $currency,
            $apiRequester,
            array_merge($this->getConfig(), ['nownodes' => ['api_key' => 'CDK4aR8E9T1rtnxPOw6G3BdYfWkcuio7']])
        );
        $transactionInstance = $crypto->createTransaction($senderWallet);
        $transaction = $transactionInstance->create([$destinationAddressDto]);

        $this->assertEquals('06a6c07fe4dee7b96e2a6b0a1c78da570462294283d2882c6af523e9c7d8517b', $transaction->getId());
        $this->assertEquals('0100000001c8b460f8013fedde9178bffd3db82bbcb79e8b7fd4a356c777088b94792a42c5010000006a47304402204f60eb8a3481044db94ef4166f07475b534a8c26ae982e76028141f23c07f8f10220407f486d2d18d1d6b39acb4cef9e64118d2d1d1274041c31860547140731531c012102a957a13d361bc070c716c5959ba5548f9c55c4f15f75f83e38fe1c799a86728fffffffff0206090000000000001976a914e8d8ac6bb0f0eabbd56e8212103797bdc5de7fab88ac641b0000000000001976a91439b2f323ba0832313d78b4cc62c4f523f4fcd68088ac00000000', $transaction->getHex());
        $this->assertEquals('678', $transaction->getFee());
    }

    public function testBroadcastExtractFeeTwoInputsOneOutput(): void
    {
        $walletGenerator = new WalletGenerator();
        $mnemonic = 'bag grid surprise pyramid waste patch song genuine viable hurry home boss kiss buffalo cook quiz unaware arrest plunge agent good dream need typical';

        $coin = new CryptoCurrencyCoin(CryptoCurrencyCoin::BTC);
        $this->assertEquals('btc', $coin->getValue());

        $senderWalletDto = $walletGenerator->generate($coin, $mnemonic, 1, true);
        $this->assertEquals('mpxxSn8pHH7Qx2CBRSmQL9NGaPRR9azc63', $senderWalletDto->address);
        $this->assertEquals('02808dcbd543ee684f77f21c65acac2e9b559ddd080cfa500477855b58abd3fdc5', $senderWalletDto->pubKey);
        $this->assertEquals('cSaCwYLH3zDnCE7JC8eUanuaHhKLUy1RT3Nys9mLQ4XsryeU31tJ', $senderWalletDto->privateKey);
        $this->assertEquals('tprv8jqkS9LaajzNqBHD17Towr5wURVAPf9Dgym2oB65z6U8Q8Gb1jDebVY9jXBat4mNH4pWTVMMbDPS699AGtscBigemmhdLzD1pZ2WM1LFiXU', $senderWalletDto->xPrv);
        $this->assertEquals('67a412982014f99563f353b952bdc73beeeaf88e', $senderWalletDto->pubKeyHash);
        $this->assertEquals('tpubDGXnaZNpj7g3ieJztm8QMFk43T16YzL8GHMp5h8PQNGXEcXMe83EmzA1udyWJUntQjzDrTLg9baLAt5us7PmptL3GN6CHkdwhat1D49egTS', $senderWalletDto->xPub);

        $currency = new Currency(CryptoCurrency::BTC(), new CurrencyNetwork(TokenType::DEFAULT(), 8));
        $this->assertEquals('btc', $currency->getCode());
        $this->assertEquals('default', $currency->getNetwork()->getTokenType());

        $senderWallet = new Wallet($senderWalletDto->address, $senderWalletDto->privateKey, $currency, $senderWalletDto->pubKey);
        $this->assertEquals('mpxxSn8pHH7Qx2CBRSmQL9NGaPRR9azc63', $senderWallet->getAddress());
        $this->assertEquals('cSaCwYLH3zDnCE7JC8eUanuaHhKLUy1RT3Nys9mLQ4XsryeU31tJ', $senderWallet->getPrivateKey());
        $this->assertEquals('02808dcbd543ee684f77f21c65acac2e9b559ddd080cfa500477855b58abd3fdc5', $senderWallet->getPublicKey());

        $apiRequester = new ApiRequester(new Client(), new NullLogger());
        $crypto = AbstractCryptoFactory::instanceByCurrency(
            $currency,
            $apiRequester,
            array_merge($this->getConfig(), ['nownodes' => ['api_key' => 'CDK4aR8E9T1rtnxPOw6G3BdYfWkcuio7']])
        );
        $balance = $crypto->createApi()->addressBalance($senderWallet->getAddress());
        $this->assertEquals(0.0002, $balance);

        $destinationAddressDto = new DestinationAddressDto([
            'address' => 'n2k8dJGvQa9Z4G5gPDogKPsopbvoGoJmaR',
            'amount' => 0.0002
        ]);

        $transactionInstance = $crypto->createTransaction($senderWallet);
        $transactionExtractFee = $transactionInstance->create([$destinationAddressDto], [], true);

        $this->assertEquals('17083613c38ae8995ac792bedd3a7874c366fe434dbd4eb743662b274e879743', $transactionExtractFee->getId());
        $this->assertEquals('010000000242cefb3cb942bc4c99d57f7ebdac41c82652c01c30b9f963b9c53db93d77312c000000006b483045022100c5ca79090ad3f039a032061c190c1152fedcd24a842b66feb5a8c6d24ee2a7b60220618cd1be5a4b62cd5cfee1f5060becb77bacc587de16da0f2e7064a06e01b8e7012102808dcbd543ee684f77f21c65acac2e9b559ddd080cfa500477855b58abd3fdc5ffffffff2f5cff97d31ea390f5dd6e9f69e4a1b7b943dc54c714ac2d353c804d4b5fca85010000006b483045022100dfa90cb985efba28f0907944a6142da40e51671abf0fbc32bdac2b42d1812c360220638bfc19c9f88318a55925de62a88f6923e9f98033d87d950399d4f521f4e46d012102808dcbd543ee684f77f21c65acac2e9b559ddd080cfa500477855b58abd3fdc5ffffffff012a4a0000000000001976a914e8d8ac6bb0f0eabbd56e8212103797bdc5de7fab88ac00000000', $transactionExtractFee->getHex());
        $this->assertEquals('1014', $transactionExtractFee->getFee());

        $this->expectException(PaymentGatewayException::class);
        $transactionInstance->create([$destinationAddressDto]);
    }

    public function testBroadcastExtractFeeTwoInputsTwoOutputs(): void
    {
        $walletGenerator = new WalletGenerator();
        $mnemonic = 'bag grid surprise pyramid waste patch song genuine viable hurry home boss kiss buffalo cook quiz unaware arrest plunge agent good dream need typical';

        $coin = new CryptoCurrencyCoin(CryptoCurrencyCoin::BTC);
        $senderWalletDto = $walletGenerator->generate($coin, $mnemonic, 2, true);
        $currency = new Currency(CryptoCurrency::BTC(), new CurrencyNetwork(TokenType::DEFAULT(), 8));

        $senderWallet = new Wallet($senderWalletDto->address, $senderWalletDto->privateKey, $currency, $senderWalletDto->pubKey);
        $this->assertEquals('n27TbnzjJNm1GxzMcTcxTEFuQRzaZJoj6f', $senderWallet->getAddress());

        $apiRequester = new ApiRequester(new Client(), new NullLogger());
        $crypto = AbstractCryptoFactory::instanceByCurrency(
            $currency,
            $apiRequester,
            array_merge($this->getConfig(), ['nownodes' => ['api_key' => 'CDK4aR8E9T1rtnxPOw6G3BdYfWkcuio7']])
        );
        $balance = $crypto->createApi()->addressBalance($senderWallet->getAddress());
        $this->assertEquals(0.0002, $balance);

        $destinationAddresses = [
            new DestinationAddressDto([
            'address' => 'n2k8dJGvQa9Z4G5gPDogKPsopbvoGoJmaR',
            'amount' => 0.00015
            ]),
            new DestinationAddressDto([
            'address' => 'mpxxSn8pHH7Qx2CBRSmQL9NGaPRR9azc63',
            'amount' => 0.00005
            ])
        ];

        $transactionInstance = $crypto->createTransaction($senderWallet);
        $transactionExtractFee = $transactionInstance->create($destinationAddresses, [], true);

        $this->assertEquals('9d9070290c668984e8cefb71f4c8f2dfcd427ffe33c90dad5693ad6f4d6026f4', $transactionExtractFee->getId());
        $this->assertEquals('01000000024d8f293295b07303195b72b909cc3e22372afeab186731c288bf83b102912d3a000000006b483045022100807f9e5bf385de2260ea8679678449598b2f6843ed8485e7a226d0881b939ba7022013c8425f32598050acc79f79ea2496552dd44712411d65b69b9d82da31996ab7012103922a72e4d305abae793b626a7d40736ffa9763717cb8c8a47218196fe5999c22ffffffff21b0afb13fc76451d586ac3c9d23ae4bac7296dcc5e7795520647ce978034fa8000000006a47304402202cc1338dd6c9719faa4270d0122cbdc8741ddb4fc07d135281ae3add34dd6e2102202225b6568559fe4415a59922774fcc127d92ae6bd30f3087ea23f4300efe154d012103922a72e4d305abae793b626a7d40736ffa9763717cb8c8a47218196fe5999c22ffffffff0239360000000000001976a914e8d8ac6bb0f0eabbd56e8212103797bdc5de7fab88ac88130000000000001976a91467a412982014f99563f353b952bdc73beeeaf88e88ac00000000', $transactionExtractFee->getHex());
        $this->assertEquals('1119', $transactionExtractFee->getFee());
    }

    public function testBroadcastExtractFeeOneInputsTwoOutputs(): void
    {
        $walletGenerator = new WalletGenerator();
        $mnemonic = 'bag grid surprise pyramid waste patch song genuine viable hurry home boss kiss buffalo cook quiz unaware arrest plunge agent good dream need typical';

        $coin = new CryptoCurrencyCoin(CryptoCurrencyCoin::BTC);
        $senderWalletDto = $walletGenerator->generate($coin, $mnemonic, 3, true);
        $currency = new Currency(CryptoCurrency::BTC(), new CurrencyNetwork(TokenType::DEFAULT(), 8));

        $senderWallet = new Wallet($senderWalletDto->address, $senderWalletDto->privateKey, $currency, $senderWalletDto->pubKey);
        $this->assertEquals('n3Wfhw8FtqyaSpQaCUXQyNvR3CFzbop9yF', $senderWallet->getAddress());

        $apiRequester = new ApiRequester(new Client(), new NullLogger());
        $crypto = AbstractCryptoFactory::instanceByCurrency(
            $currency,
            $apiRequester,
            array_merge($this->getConfig(), ['nownodes' => ['api_key' => 'CDK4aR8E9T1rtnxPOw6G3BdYfWkcuio7']])
        );
        $balance = $crypto->createApi()->addressBalance($senderWallet->getAddress());
        $this->assertEquals(0.00033, $balance);

        $destinationAddresses = [
            new DestinationAddressDto([
            'address' => 'n2k8dJGvQa9Z4G5gPDogKPsopbvoGoJmaR',
            'amount' => 0.00015
            ]),
            new DestinationAddressDto([
                'address' => 'mpxxSn8pHH7Qx2CBRSmQL9NGaPRR9azc63',
                'amount' => 0.00015
            ]),
        ];

        $transactionInstance = $crypto->createTransaction($senderWallet);
        $transactionExtractFee = $transactionInstance->create($destinationAddresses, [], true);

        $this->assertEquals('076103da31f1c0f4fc5788882be9515602c6c861b2f580e76f1e7d19d5739c7c', $transactionExtractFee->getId());
        $this->assertEquals('01000000012a0ddcfe0193f34eb6505f32a332b04109ff38dc1a13dc5a3b64cbe54d12200b010000006b483045022100e8b9ed0fe8f432b5a084a6623439e9a99b9f2d25bc7b00b48b557487d0c5c962022052f4f88aa2e542980ccacd0295677b0680e85c79e970ae2899fae8667a8c5f850121020855c759782be7bb704428a1d7e5ff24c035fcb07fc04c7f1a054ffa6ba5acecffffffff038f370000000000001976a914e8d8ac6bb0f0eabbd56e8212103797bdc5de7fab88ac983a0000000000001976a91467a412982014f99563f353b952bdc73beeeaf88e88acb80b0000000000001976a914f144eb883ce5e6ab59505c4eb7438b5798509ad388ac00000000', $transactionExtractFee->getHex());
        $this->assertEquals('777', $transactionExtractFee->getFee());
    }

    /**
     * Sender wallet have 2 inputs. Each input consider 1000 satoshi.
     * @see https://www.blockchain.com/ru/btc-testnet/address/mk6QczQSuCNcADniimNe3J3cetTmFo3V9K
     * Try to send 500 satoshi to another wallet.
     *
     * @throws PaymentGatewayException
     */
    public function testBroadcastSmallTransfer(): void
    {
        $walletGenerator = new WalletGenerator();
        $mnemonic = 'bag grid surprise pyramid waste patch song genuine viable hurry home boss kiss buffalo cook quiz unaware arrest plunge agent good dream need typical';

        $coin = new CryptoCurrencyCoin(CryptoCurrencyCoin::BTC);
        $senderWalletDto = $walletGenerator->generate($coin, $mnemonic, 4, true);
        $currency = new Currency(CryptoCurrency::BTC(), new CurrencyNetwork(TokenType::DEFAULT(), 8));

        $senderWallet = new Wallet($senderWalletDto->address, $senderWalletDto->privateKey, $currency, $senderWalletDto->pubKey);
        $this->assertEquals('mk6QczQSuCNcADniimNe3J3cetTmFo3V9K', $senderWallet->getAddress());

        $apiRequester = new ApiRequester(new Client(), new NullLogger());
        $crypto = AbstractCryptoFactory::instanceByCurrency(
            $currency,
            $apiRequester,
            array_merge($this->getConfig(), ['nownodes' => ['api_key' => 'CDK4aR8E9T1rtnxPOw6G3BdYfWkcuio7']])
        );
        $balance = $crypto->createApi()->addressBalance($senderWallet->getAddress());
        $this->assertEquals(0.00002000, $balance);

        $destinationAddresses = [
            new DestinationAddressDto([
                'address' => 'n2k8dJGvQa9Z4G5gPDogKPsopbvoGoJmaR',
                'amount' => 0.00000500
            ])
        ];

        $transactionInstance = $crypto->createTransaction($senderWallet);
        $transactionExtractFee = $transactionInstance->create($destinationAddresses, [], false);

        $this->assertEquals('ac6800d3b849aa036fc367d2a0c7b87d0b493cd57c94d22562b62902503209f6', $transactionExtractFee->getId());
        $this->assertEquals('0100000002c098d4d2ba93b844d15f922dbf099e0bbef0c2ac908a9c27ce9f76aaadfddce9000000006a47304402201de27810329db96d947d16d28ce5203730c4157d54d932a8ff70f89ea0323ad502206289d6a5e17f8975f6e7bcdd7e194b1350cc3099b8fd0913952325bd53a857db01210258b011a522ddfe6da1f8422ca7b821676a992d06877a4d14723868132ac8605ffffffffffe4dc646112b0be5e512866442bd000b019d7731166109f66a27115cc229fcee010000006a47304402201e14e7c41c56bd33906dfd0418c5b4aa7cbab63de3559aa35b6719bec1988ddc0220404d1636bedd398126e7da373c7eeb62fcf9a9e4996df5acecac7c798f526f3001210258b011a522ddfe6da1f8422ca7b821676a992d06877a4d14723868132ac8605fffffffff02f4010000000000001976a914e8d8ac6bb0f0eabbd56e8212103797bdc5de7fab88ac80010000000000001976a914323434c476941e26f9a6c0851bf9d0f96a52ec3f88ac00000000', $transactionExtractFee->getHex());
        $this->assertEquals('1116', $transactionExtractFee->getFee());
    }

    private function getConfig(): array
    {
        return require 'config/crypto.php';
    }
}
