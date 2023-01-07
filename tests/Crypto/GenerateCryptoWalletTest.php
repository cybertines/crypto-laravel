<?php
declare(strict_types=1);

namespace App\CryptoGatewayEngine\Tests\Crypto;

use App\CryptoGatewayEngine\Dto\WalletDto;
use App\CryptoGatewayEngine\Tests\TestCase;
use App\CryptoGatewayEngine\Entity\CryptoCurrencyCoin;
use App\CryptoGatewayEngine\WalletGenerator\WalletGenerator;

class GenerateCryptoWalletTest extends TestCase
{
    private string $seed = 'recall code question stone april large grab fatal aware wolf shine measure silly draw funny patch tilt cigar alter club index sister armed purse';
    private WalletGenerator $walletGenerator;

    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->walletGenerator = new WalletGenerator();
    }

    public function test_generate_btc_address(): void
    {
        $btc = $this->walletGenerator->generate(new CryptoCurrencyCoin(CryptoCurrencyCoin::BTC), $this->seed);
        self::assertJsonStringEqualsJsonString(
            json_encode($btc->toArray()),
            json_encode((new WalletDto([
                'address'    => '1FkhvXdjcY5juJgvmbygGhx6kmaGLxpEUp',
                'xPrv'       => 'xprvA42snfisf1fyQ2mFii6cCFUHMhtU8UrueaAnvYkYLuYoPSd2CCar7CoKpHRPMzaVo6LRfcZfFn5z62aQoxkqhun8V14ucAd9u1qFpgLY3Yn',
                'privateKey' => 'KzS6hgHAfnuo3m8FVgt1sYsu5W4BJBk8FKkhokvKQr62ePqiFUqN',
                'pubKey'     => '039c25a8fd0018a950432a82efee225a1324f7f7e69f500613f5607123fa757211',
                'pubKeyHash' => 'a1d5cedc180c82f81195400b8f340780369578d4',
                'xPub'       => 'xpub6H2ECBFmVPEGcWqipjdcZPR1ujixXwam1o6PiwA9uF5nGExAjju6f17ofarSHsTHaD6F3mvT7yc6KQQT7Pw7nHpZ27en6Jws1rLG5j26zKA',
                'coin'       => 'btc',
                'path'       => 'm/44\'/0\'/0\'/0/0'
            ]))->toArray())
        );

        $btcTest = $this->walletGenerator->generate(
            new CryptoCurrencyCoin(CryptoCurrencyCoin::BTC),
            $this->seed,
            0,
            true
        );
        self::assertJsonStringEqualsJsonString(
            json_encode($btcTest->toArray()),
            json_encode((new WalletDto([
                'address'    => 'n4Z1y3Sgy3GhGoKMa3RVSjUA9Nj7hroAiK',
                'xPrv'       => 'tprv8kaCqxsNcTgP1GDSiEUZFQJqEqfCs63TyxEkGhMqzJrfLsQv5bbY5cxpL8pNLXcd2Rpre15boeKw7uBe4va54yaZAuGTH3MoWz4k3UaAkxi',
                'privateKey' => 'cRNxTuqwhNKV9dcxBokKEFKyMQLjrgHPytxqMZcauM7xkueDc1xt',
                'pubKey'     => '02d2b93505a8aa41c34e9df3782530635387e19296c8a027c593ad0d5fe0a897e1',
                'pubKeyHash' => 'fcaeca558787c947ad83e36f31f2929fe1d77e08',
                'xPub'       => 'tpubDHGEzNuckqN3tjFEbt99eoxwosB92RENZFqXZDQ9Qaf4BMfghzR8G7agWGWAepRTuGuPnQrzayzEZhtBkD5jJk59VKiQbegSabN44C1UtUe',
                'coin'       => 'btc',
                'path'       => 'm/44\'/1\'/0\'/0/0'
            ]))->toArray())
        );
    }

    public function test_generate_eth_address(): void
    {
        $eth = $this->walletGenerator->generate(new CryptoCurrencyCoin(CryptoCurrencyCoin::ETH), $this->seed);
        self::assertJsonStringEqualsJsonString(
            json_encode($eth->toArray()),
            json_encode((new WalletDto([
                'address'    => '0xa889D96cb2E727be0fb22B3450086A9ac7004C1C',
                'xPrv'       => 'xprvA41kBiWaFy1VHSDnrMEUx5ExkTRvTf2kY2rH7bRasrkUYARK1ShtWUd2mvtE6VtbbeFs5pz46YC5BmPRpqUNB4WgSJHG2PmCJrCECAAMXaj',
                'privateKey' => '0x19478b024717eeec22125115ceffcc3cae2d9aac68796e52151ca87ed207516c',
                'pubKey'     => '0x038bbacb8a7d1825d67110a432d618454441eb587ab7f1c44995e8d1fd235c274e',
                'pubKeyHash' => 'cbceb5fbfe3e49ace33d8a8c81b2dede3f06e3f4',
                'xPub'       => 'xpub6H16bE3U6LZnVvJFxNmVKDBhJVGQs7kbuFmsuyqCSCHTQxkTYz294GwWdEj9C2U9XPQgzCSaSPZZujqfDuiLKEkvbHNKfEu6bqpJrES5KrC',
                'coin'       => 'eth',
                'path'       => 'm/44\'/60\'/0\'/0/0'
            ]))->toArray())
        );

        $ethTest = $this->walletGenerator->generate(
            new CryptoCurrencyCoin(CryptoCurrencyCoin::ETH),
            $this->seed,
            0,
            true
        );
        self::assertJsonStringEqualsJsonString(
            json_encode($ethTest->toArray()),
            json_encode((new WalletDto([
                'address'    => '0xFdF4d699d048046B75b10E3C92a368360A02c79E',
                'xPrv'       => 'tprv8kaCqxsNcTgP1GDSiEUZFQJqEqfCs63TyxEkGhMqzJrfLsQv5bbY5cxpL8pNLXcd2Rpre15boeKw7uBe4va54yaZAuGTH3MoWz4k3UaAkxi',
                'privateKey' => '0x714ec871825aac7994ec0d6440cd3c634cbf15e405991dc729a4651959291016',
                'pubKey'     => '0x02d2b93505a8aa41c34e9df3782530635387e19296c8a027c593ad0d5fe0a897e1',
                'pubKeyHash' => 'fcaeca558787c947ad83e36f31f2929fe1d77e08',
                'xPub'       => 'tpubDHGEzNuckqN3tjFEbt99eoxwosB92RENZFqXZDQ9Qaf4BMfghzR8G7agWGWAepRTuGuPnQrzayzEZhtBkD5jJk59VKiQbegSabN44C1UtUe',
                'coin'       => 'eth',
                'path'       => 'm/44\'/1\'/0\'/0/0'
            ]))->toArray())
        );
    }

    public function test_generate_trx_address(): void
    {
        $trx = $this->walletGenerator->generate(new CryptoCurrencyCoin(CryptoCurrencyCoin::TRX), $this->seed);
        self::assertJsonStringEqualsJsonString(
            json_encode($trx->toArray()),
            json_encode((new WalletDto([
                'address'    => 'TAQVYnindPV9hNBdAucpF365kFRTbWciwt',
                'xPrv'       => 'xprvA2UwrM6z6rNseKKwA8iJ7cRetKz8SsGt8kok9HXWs3niTNCYjsJYBGtzu5or3SMyyyV4DkweVDLm95PrqtnAdYLJC1smgLdmZ8oyCy9TtWM',
                'privateKey' => '630662c5169ab3a5f17f632fe682904815126e54c0772f90293f9e8e46ee8fb4',
                'pubKey'     => '02489c64a999ab144dde6e3dc7079f03d068d6465be8ccd1d70c76d1f0ef2f1c23',
                'pubKeyHash' => 'b45433c8c60a03c70ccc05e852f0cc50159fbd41',
                'xPub'       => 'xpub6FUJFrdswDwAroQQGAFJUkNPSMpcrKzjVyjLwfw8RPKhLAXhHQcnj5DUkLdymqUhWJGiEmvjhtUPQSnjBJaT99M4Vp8b11TxLUfKXPTZdaS',
                'coin'       => 'trx',
                'path'       => 'm/44\'/195\'/0\'/0/0'
            ]))->toArray())
        );
    }

    public function test_generate_xrp_address(): void
    {
        $xrp = $this->walletGenerator->generate(new CryptoCurrencyCoin(CryptoCurrencyCoin::XRP), $this->seed);
        self::assertJsonStringEqualsJsonString(
            json_encode($xrp->toArray()),
            json_encode((new WalletDto([
                'address'    => 'r4rYJnxSh54YPRyuUgtSSQC4ws7RTqjH1D',
                'xPrv'       => 'xprvA3vq2se38xU6w8gPzqDsTUvFPe4nAXZ3VG4AoWtXiKcAJAdxGF2cTpW7PH4jL7ppmZrqWDF9XvtDD7ry4nxbpWfaYp3AXAKBHn3iaPMcjxJ',
                'privateKey' => '1aa91e3440c0cf8d4c1f7a21da68008a5456fa5d9989f7124eabb11400b60671',
                'pubKey'     => '02afd5187fd437c78953c6d60ebc257efc9a24184605aa03c8225c7692f5bf74c8',
                'pubKeyHash' => 'e674c3cd9fb75754a8aee41d7badf73577af5382',
                'xPub'       => 'xpub6GvBSPAvyL2Q9cks6rkspcrywfuGZzGtrUymbuJ9Gf99Axy6onLs1cpbEZECAZJeSKtMq37xEhR2hDnfFACawJXAU7Ej25gXqiozb6V75BP',
                'coin'       => 'xrp',
                'path'       => 'm/44\'/144\'/0\'/0/0'
            ]))->toArray())
        );
    }

    public function test_generate_ltc_address(): void
    {
        $ltc = $this->walletGenerator->generate(new CryptoCurrencyCoin(CryptoCurrencyCoin::LTC), $this->seed);
        self::assertJsonStringEqualsJsonString(
            json_encode($ltc->toArray()),
            json_encode((new WalletDto([
                'address'    => 'LWMsaNuDu7g4KP7mcoFwFDGmq71FZ1FMYh',
                'xPrv'       => 'xprvA4CTKdJ9pdHaKiwKzApHGEB2BqaLh3QTphJCATtq33aM3k8g7ZRU4cVgGkgZs3CDmGBhYW53xmsis7bjaaH4H88k8WdVf9Xi9H81i93RYaY',
                'privateKey' => 'T7JRANAWkvqsTVVt1NmouZKMa9fEzLzyYtQAxo19Z3iFZLj87F1j',
                'pubKey'     => '038b12c3e525622c9744c0b36541c7670866362f87cc5ce4b181f3120a3ec14e71',
                'pubKeyHash' => '7a28cc8940e2cf6e810b5023279174f2c64acd4b',
                'xPub'       => 'xpub6HBoj8q3ezqsYD1o6CMHdN7kjsQq6W8KBvDnxrJSbP7KvYTpf6jicQpA83kVYWXMoHQKTdzCLFBk6c5FFSTRtkWp9T1XxjNSYehEJx1rzSW',
                'coin'       => 'ltc',
                'path'       => 'm/44\'/2\'/0\'/0/0'
            ]))->toArray())
        );

        $ltcTest = $this->walletGenerator->generate(
            new CryptoCurrencyCoin(CryptoCurrencyCoin::LTC),
            $this->seed,
            0,
            true
        );
        self::assertJsonStringEqualsJsonString(
            json_encode($ltcTest->toArray()),
            json_encode((new WalletDto([
                'address'    => 'n4Z1y3Sgy3GhGoKMa3RVSjUA9Nj7hroAiK',
                'xPrv'       => 'tprv8kaCqxsNcTgP1GDSiEUZFQJqEqfCs63TyxEkGhMqzJrfLsQv5bbY5cxpL8pNLXcd2Rpre15boeKw7uBe4va54yaZAuGTH3MoWz4k3UaAkxi',
                'privateKey' => 'cRNxTuqwhNKV9dcxBokKEFKyMQLjrgHPytxqMZcauM7xkueDc1xt',
                'pubKey'     => '02d2b93505a8aa41c34e9df3782530635387e19296c8a027c593ad0d5fe0a897e1',
                'pubKeyHash' => 'fcaeca558787c947ad83e36f31f2929fe1d77e08',
                'xPub'       => 'tpubDHGEzNuckqN3tjFEbt99eoxwosB92RENZFqXZDQ9Qaf4BMfghzR8G7agWGWAepRTuGuPnQrzayzEZhtBkD5jJk59VKiQbegSabN44C1UtUe',
                'coin'       => 'ltc',
                'path'       => 'm/44\'/1\'/0\'/0/0'
            ]))->toArray())
        );
    }
}
