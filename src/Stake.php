<?php

namespace Tron;

use IEXBase\TronAPI\Exception\TronException;
use Tron\Exceptions\TronErrorException;
use Tron\Exceptions\TransactionException;

class Stake extends TRX
{
    public function __construct(Api $_api, string $private_key = '')
    {
        parent::__construct($_api);

        if ($private_key) {
            $account = $this->privateKeyToAddress($private_key);
            $this->tron->setAddress($account->address);
            $this->tron->setPrivateKey($account->privateKey);
        }
    }

    /**
     * 质押TRX
     */
    public function freezeBalanceV2(string $address, float $amount, string $resource = 'ENERGY')
    {
        return $this->tron->freezeBalanceV2($amount, $resource, $address);
    }

    /**
     * 解质押TRX
     * 解锁通过Stake2.0机制质押的TRX, 释放所相应数量的带宽和能量，同时回收相应数量的投票权(TP)
     */
    public function unfreezeBalanceV2(string $address, float $amount, string $resource = 'ENERGY')
    {
        return $this->tron->unfreezeBalanceV2($amount, $resource, $address);
    }

    /**
     * 将带宽或者能量资源代理给其它账户
     */
    public function delegate(string $to_address, float $amount, string $resource = 'ENERGY', $lock = false, $lock_period = 0)
    {
        try {
            $response = $this->tron->sendDelegate($to_address, $amount, $resource, $lock, $lock_period);
        } catch (TronException $e) {
            throw new TransactionException($e->getMessage(), $e->getCode());
        }

        if (!isset($response['result']) || $response['result'] != true) {
            throw new TransactionException(hex2bin($response['message']));
        }

        return new Transaction(
            $response['txID'],
            $response['raw_data'],
            'DelegateResourceContract'
        );
    }

    /**
     * 取消为目标地址代理的带宽或者能量
     */
    public function undelegate(string $to_address, float $amount, string $resource = 'ENERGY')
    {
        try {
            $response = $this->tron->sendUnDelegate($to_address, $amount, $resource);
        } catch (TronException $e) {
            throw new TransactionException($e->getMessage(), $e->getCode());
        }

        if (!isset($response['result']) || $response['result'] != true) {
            throw new TransactionException(hex2bin($response['message']));
        }

        return new Transaction(
            $response['txID'],
            $response['raw_data'],
            'UnDelegateResourceContract'
        );
    }

    /**
     * 质押1trx获得的能量
     */
    public function getFrozenEnergyPrice($address = null)
    {
        try {
            $accountres = $this->tron->getAccountResources($address);
        } catch (TronException $e) {
            throw new TronErrorException($e->getMessage(), $e->getCode());
        }

        if (empty($accountres)) {
            throw new TronErrorException("Account is not actived!", 100);
        }

        return $accountres['TotalEnergyLimit'] / $accountres['TotalEnergyWeight'];
    }

    /**
     * 质押1trx获得的带宽
     */
    public function getFrozenNetPrice($address = null)
    {
        try {
            $accountres = $this->tron->getAccountResources($address);
        } catch (TronException $e) {
            throw new TronErrorException($e->getMessage(), $e->getCode());
        }

        if (empty($accountres)) {
            throw new TronErrorException("Account is not actived!", 100);
        }

        return $accountres['TotalNetLimit'] / $accountres['TotalNetWeight'];
    }
}
