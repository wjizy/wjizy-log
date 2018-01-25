<?php
/**
 * Created by PhpStorm.
 * User: 112363
 * Date: 2018/1/25
 * Time: 下午 02:11
 */

namespace wjizyLog\api;


class Logger
{
    const  ERROR = 1;
    const  WARN  = 2;
    const  DEBUG = 3;
    const  INFO  = 4;

    private $error_dir_file = null;
    private $debug_dir_file = null;
    private $warn_dir_file  = null;
    private $info_dir_file  = null;

    private $errorMessage   = '';
    private $filePermission;
    private $useLocking;
    function __construct($dir_file_array, $useLocking=false, $filePermission=0644)
    {
        if(!is_array($dir_file_array)){
            throw new \InvalidArgumentException('日志目录必须是数组！键值eg. 日志目录=>错误等级');
        }
        foreach ($dir_file_array as $level => $dir){
            ($level == self::ERROR) && $this->error_dir_file = $dir;
            ($level == self::WARN)  && $this->warn_dir_file  = $dir;
            ($level == self::DEBUG) && $this->debug_dir_file = $dir;
            ($level == self::INFO)  && $this->info_dir_file  = $dir;
        }
        $this->filePermission = $filePermission;
        $this->useLocking     = $useLocking;
    }

    public function debug($msg)
    {
        $this->addRecord($msg, self::DEBUG);
    }

    public function error($msg)
    {
        $this->addRecord($msg, self::ERROR);
    }

    public function info($msg)
    {
        $this->addRecord($msg, self::INFO);
    }

    public function warn($msg)
    {
        $this->addRecord($msg, self::WARN);
    }

    private function addRecord($msg, $level)
    {
        $this->createDir($level);

        $dir_file = $this->getLogFile($level);

        $this->errorMessage = '';

        set_error_handler(array($this, 'errorMsg'));
        $stream = fopen($dir_file,'a');
        @chmod($dir_file, $this->filePermission);
        restore_error_handler();

        if(!is_resource($stream)){
            throw new \UnexpectedValueException(sprintf('打开文件%s失败!errorMsg: %s', $dir_file, $this->errorMessage));
        }

        $formateMsg = $this->formateMsg($msg,$level);
        $this->writeRecord($stream, $formateMsg);
    }

    private function writeRecord( $stream , $formateMsg)
    {
        if ($this->useLocking) {
            flock($stream, LOCK_EX);
        }

        fwrite($stream, $formateMsg);

        if ($this->useLocking) {
            flock($stream, LOCK_UN);
        }
    }

    private function formateMsg($msg, $level)
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS,1);
        $file  = $trace[0]['file'];
        $line  = $trace[0]['line'];

        $time  = date('Y-m-d H:i:s',time());
        $prefix= $this->getLogPrefix($level);

        return $prefix.':'.$time.' '.$file.':'.$line.' '.$msg.PHP_EOL;
    }

    public function getLogPrefix($level)
    {
        $prefix = 'info';
        ($level == self::ERROR) && $prefix='ERROR';
        ($level == self::WARN)  && $prefix='WARN';
        ($level == self::DEBUG) && $prefix='DEBUG';
        ($level == self::INFO)  && $prefix='INFO';

        return $prefix;
    }

    private function getLogFile($level)
    {
        $dir = null;
        ($level == self::ERROR) && $dir = $this->error_dir_file;
        ($level == self::WARN)  && $dir = $this->warn_dir_file ;
        ($level == self::DEBUG) && $dir = $this->debug_dir_file;
        ($level == self::INFO)  && $dir = $this->info_dir_file;
        return $dir;
    }

    private function createDir($level)
    {
        $dir_file  = $this->getLogFile($level);
        $dir       = dirname($dir_file);
        if(is_null($dir)){
            throw new \InvalidArgumentException('日志等级对应的文件路径不存在'.PHP_EOL);
        }
        if(is_dir($dir)){
            return;
        }
        set_error_handler(array($this, 'errorMsg'));
        $status = mkdir($dir,0777, true);
        restore_error_handler();
        if($status === false){
            throw new \UnexpectedValueException(sprintf('日志文件创建失败!errorMsg: %s'.PHP_EOL,$this->errorMessage));
        }
    }

    private function errorMsg($code, $msg)
    {
        $this->errorMessage = preg_replace('{^(fopen|mkdir)\(.*?\): }', '', $msg);
    }
}