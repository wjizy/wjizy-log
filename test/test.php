<?php
/**
 * Created by PhpStorm.
 * User: 112363
 * Date: 2018/1/25
 * Time: 下午 05:30
 */
namespace wjizyLog\test;
use wjizyLog\api\Logger;

include_once '../vendor/autoload.php';

class test{
   function __construct()
   {
       $log_config = [
           Logger::INFO     =>  '/data/root/wjizy-log/test/info-'.date('Ymd',time()).'.log',
           Logger::DEBUG    =>  '/data/root/wjizy-log/test/debug-'.date('Ymd',time()).'.log',
           Logger::ERROR    =>  '/data/root/wjizy-log/test/error-'.date('Ymd',time()).'.log',
           Logger::WARN     =>  '/data/root/wjizy-log/test/warn-'.date('Ymd',time()).'.log',
        ];
       $logger = new Logger($log_config);
       $logger->info(json_encode(['a'=>1111111]));
       $logger->debug('hello world');
       $logger->error('wjizy');
       $logger->warn('我是中国人');
   }
}
new test();

