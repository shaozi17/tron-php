<?php

namespace Tron;

use IEXBase\TronAPI\Exception\TronException;
use Tron\Exceptions\TransactionException;

class TRX extends TRON
{
    public function balance(?string $address = null)
    {
        return $this->tron->getBalance($address, true);
    }

    public function transfer(string $to_address, float $amount, $message = null): Transaction
    {
        try {
            $response = $this->tron->sendTransaction($to_address, $amount, $message, $this->account->address);
        } catch (TronException $e) {
            throw new TransactionException($e->getMessage(), $e->getCode());
        }

        if (!isset($response['result']) || $response['result'] != true) {
            throw new TransactionException(hex2bin($response['message']));
        }

        return new Transaction(
            $response['txID'],
            $response['raw_data'],
            'PACKING'
        );
    }
}
