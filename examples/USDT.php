<?php
require("./vendor/autoload.php");

use GuzzleHttp\Client;
use IEXBase\TronAPI\Exception\TronException;
use Tron\Api;
use Tron\TRC20;

$from_privtekey = "0000000";                                           //波场私钥
$to_address     = "Txxxxxx";                                           //波场公钥（波场地址）


// $trc20Wallet = new TRX('https://api.trongrid.io', $from_privtekey);         //正式链
$trc20Wallet = new TRC20('https://api.shasta.trongrid.io', $from_privtekey);  //测试链

var_dump($trc20Wallet->getAccount());  //Adress类中获取公钥
var_dump($trc20Wallet->balance());     //获取余额

try {
    $transferData = $trc20Wallet->transfer($fromAddr, $to_address, 1);  //转账1usdt
} catch (TronException $e) {
    debug_print_backtrace();
    var_dump($e->getMessage(), $e->getCode());
} catch (\Exception $e) {
    debug_print_backtrace();
    var_dump($e->getMessage());
}

var_dump($transferData);
