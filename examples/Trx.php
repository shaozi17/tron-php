<?php
require("./vendor/autoload.php");

use IEXBase\TronAPI\Exception\TronException;
use Tron\TRX;

$from_privtekey = "0000000";  //波场私钥
$to_address     = "Txxxxxx";  //波场公钥（波场地址）

// $trxWallet = new TRX('https://api.trongrid.io', $from_privtekey);         //正式链
$trxWallet = new TRX('https://api.shasta.trongrid.io', $from_privtekey);  //测试链

var_dump($trxWallet->getAccount());  //Adress类中获取公钥
var_dump($trxWallet->balance());     //获取trx余额

try {
    $transferData = $trxWallet->transfer($to_address, 1);  //转账1trx
} catch (TronException $e) {
    debug_print_backtrace();
    var_dump($e->getMessage(), $e->getCode());
} catch (\Exception $e) {
    debug_print_backtrace();
    var_dump($e->getMessage());
}
var_dump($transferData);
