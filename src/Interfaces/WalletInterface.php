<?php

namespace Tron\Interfaces;

use Tron\Address;
use Tron\Block;
use Tron\Transaction;

interface WalletInterface
{
    public function generateAddress(): Address;

    public function validateAddress(Address $address): bool;

    public function privateKeyToAddress(string $privateKeyHex): Address;

    public function balance(string $address);

    public function transfer(Address $account, string $to, float $amount, $message = null): Transaction;

    public function blockNumber(): Block;

    public function blockByNumber(int $blockID): Block;

    public function transactionReceipt(string $txHash);
}
