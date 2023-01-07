<?php
declare(strict_types=1);

namespace App\CryptoGatewayEngine\Entity;

use App\CryptoGatewayEngine\Dto\UtxOutputDto;

class AddressUtxo
{
    private string $address;
    private int $balance = 0;
    /** @var UtxOutputDto[] */
    private array $utxo = [];

    public function __construct(string $address)
    {
        $this->address = $address;
    }

    public function getBalance(): int
    {
        return $this->balance;
    }

    public function getAddress(): string
    {
        return $this->address;
    }

    public function addUtxo(string $txHash, int $index, int $value, Wallet $wallet)
    {
        $this->utxo[] = new UtxOutputDto([
            'hash'  => $txHash,
            'index' => $index,
            'value' => $value,
            'wallet' => $wallet
        ]);
        $this->balance += $value;
    }

    /**
     * @param int $amount
     * @return UtxOutputDto[]
     */
    public function suitableOutputs(int $amount): array
    {
        usort($this->utxo, fn(UtxOutputDto $out, UtxOutputDto $out2) => $out2->value <=> $out->value);

        $collectAmount = 0;
        $outputs = [];

        foreach ($this->utxo as $utxo) {
            if (!$utxo->value) {
                continue;
            }

            if ($collectAmount >= $amount) {
                break;
            }

            $collectAmount += $utxo->value;
            $outputs[] = $utxo;
        }

        return $outputs;
    }
}
