<?php
namespace Tool\qrcode;

/**
 * @author xhq <1293812979@qq.com>
 * Date: 14-1-27 下午4:47
 */

 /*SeasLog 共将日志分成8个级别

    SEASLOG_DEBUG "debug"
    SEASLOG_INFO "info"
    SEASLOG_NOTICE "notice"
    SEASLOG_WARNING "warning"
    SEASLOG_ERROR "error"
    SEASLOG_CRITICAL "critical"
    SEASLOG_ALERT "alert"
    SEASLOG_EMERGENCY "emergency"*/


    /* 预警的配置
	[base]
	wait_analyz_log_path = /log/base_test

	[fork]
	;是否开启多线程 1开启 0关闭
	fork_open = 1

	;线程个数
	fork_count = 3

	[warning]
	email[smtp_host] = smtp.163.com
	email[smtp_port] = 25
	email[subject_pre] = 预警邮件 -
	email[smtp_user] = seaslogdemo@163.com
	email[smtp_pwd] = seaslog#demo
	email[mail_from] = seaslogdemo@163.com
	email[mail_to] = gaochitao@weiboyi.com
	email[mail_cc] = ciogao@gmail.com
	email[mail_bcc] =

	[analyz]
	; enum
	; SEASLOG_DEBUG      "debug"
	; SEASLOG_INFO       "info"
	; SEASLOG_NOTICE     "notice"
	; SEASLOG_WARNING    "warning"
	; SEASLOG_ERROR      "error"
	; SEASLOG_CRITICAL   "critical"
	; SEASLOG_ALERT      "alert"
	; SEASLOG_EMERGENCY  "emergency"

	test1[module] = test/bb
	test1[level] = SEASLOG_ERROR
	test1[bar] = 1
	test1[mail_to] = gaochitao@weiboyi.com

	test2[module] = 222
	test2[level] = SEASLOG_WARNING

	test3[module] = 333
	test3[level] = SEASLOG_CRITICAL

	test4[module] = 444
	test4[level] = SEASLOG_EMERGENCY

	test5[module] = 555
	test5[level] = SEASLOG_DEBUG*/

class SeasLogRecord {
    

    public static function getIpUri(){
        return 'ip:'.$_SERVER['REMOTE_ADDR'].' , runtime:{runtime}ms'.' , memorycache:{cache}kb , cmd:'.$_SERVER['REQUEST_URI'].' , data:';
    }
 
    /**
     * 设置basePath
     * @param $basePath
     * @return bool
     */
    public static function setBasePath($basePath)
    {
        // return TRUE;
        \SeasLog::setBasePath($basePath);
    }
 
    /**
     * 获取basePath
     * @return string
     */
    public static function getBasePath()
    {
        return \SeasLog::getBasePath();
    }
 
    /**
     * 设置模块目录
     * @param $module
     * @return bool
     */
    public static function setLogger($module)
    {
        \SeasLog::setLogger($module);
        // return TRUE;
    }
 
    /**
     * 获取最后一次设置的模块目录
     * @return string
     */
    public static function getLastLogger()
    {
        return \SeasLog::getLastLogger();;
    }
 
    /**
     * 统计所有类型（或单个类型）行数
     * @param string $level
     * @param string $log_path
     * @param null $key_word
     * @return array | long
     */
    public static function analyzerCount($level = 'all',$key_word = NULL,$log_path = '')
    {
        return \SeasLog::analyzerCount($level,$log_path,$key_word);
    }
 
    /**
     * 以数组形式，快速取出某类型log的各行详情
     *
     * @param        $level
     * @param string $log_path
     * @param null   $key_word
     * @param int    $start
     * @param int    $limit
     * @param        $order
     *
     * @return array
     */
    // SeasLog在扩展中使用管道调用shell命令 grep -w快速地取得列表，并返回array给PHP
    public static function analyzerDetail($level = SEASLOG_INFO, $key_word = NULL, $order = 'ASC' , $start = 1 , $limit = 20 , $log_path = '')
    {
        if($order == 'DESC'){
            $order = SEASLOG_DETAIL_ORDER_DESC;
        }else{
            $order = SEASLOG_DETAIL_ORDER_ASC;
        }
        return \SeasLog::analyzerDetail($level, $log_path, $key_word, $start, $limit, $order);
    }
 
    /**
     * 获得当前日志buffer中的内容
     * @return array
     */
    public static function getBuffer()
    {
        return array();
    }
 
    /**
     * 将buffer中的日志立刻刷到硬盘
     *
     * @return bool
     */
    public static function flushBuffer()
    {
        return TRUE;
    }
 
    /**
     * 记录debug日志
     * @param $message
     * @param array $content
     * @param string $module
     */
    public static function debug($message,array $content = array(),$module = '')
    {
        #$level = SEASLOG_DEBUG
        $message = self::getIpUri().$message;
        if($module !== ''){
            $module = $_SERVER['SERVER_NAME'].'/'.$module;
        }
        \SeasLog::debug($message,$content,$module);
    }
 
    /**
     * 记录info日志
     * @param $message
     * @param array $content
     * @param string $module
     */
    public static function info($message,array $content = array(),$module = '')
    {
        #$level = SEASLOG_INFO
        $message = self::getIpUri().$message;
        if($module !== ''){
            $module = $_SERVER['SERVER_NAME'].'/'.$module;
        }
        \SeasLog::info($message,$content,$module);
    }
 
    /**
     * 记录notice日志
     * @param $message
     * @param array $content
     * @param string $module
     */
    public static function notice($message,array $content = array(),$module = '')
    {
        #$level = SEASLOG_NOTICE
        $message = self::getIpUri().$message;
        if($module !== ''){
            $module = $_SERVER['SERVER_NAME'].'/'.$module;
        }
        \SeasLog::notice($message,$content,$module);
    }
 
    /**
     * 记录warning日志
     * @param $message
     * @param array $content
     * @param string $module
     */
    public static function warning($message,array $content = array(),$module = '')
    {
        #$level = SEASLOG_WARNING
        $message = self::getIpUri().$message;
        if($module !== ''){
            $module = $_SERVER['SERVER_NAME'].'/'.$module;
        }
        \SeasLog::warning($message,$content,$module);
    }
 
    /**
     * 记录error日志
     * @param $message
     * @param array $content
     * @param string $module
     */
    public static function error($message,array $content = array(),$module = '')
    {
        #$level = SEASLOG_ERROR
        $message = self::getIpUri().$message;
        if($module !== ''){
            $module = $_SERVER['SERVER_NAME'].'/'.$module;
        }
        \SeasLog::error($message,$content,$module);
    }
 
    /**
     * 记录critical日志
     * @param $message
     * @param array $content
     * @param string $module
     */
    public static function critical($message,array $content = array(),$module = '')
    {
        #$level = SEASLOG_CRITICAL
        $message = self::getIpUri().$message;
        if($module !== ''){
            $module = $_SERVER['SERVER_NAME'].'/'.$module;
        }
        \SeasLog::critical($message,$content,$module);
    }
 
    /**
     * 记录alert日志
     * @param $message
     * @param array $content
     * @param string $module
     */
    public static function alert($message,array $content = array(),$module = '')
    {
        #$level = SEASLOG_ALERT
        $message = self::getIpUri().$message;
        if($module !== ''){
            $module = $_SERVER['SERVER_NAME'].'/'.$module;
        }
        \SeasLog::alert($message,$content,$module);
    }
 
    /**
     * 记录emergency日志
     * @param $message
     * @param array $content
     * @param string $module
     */
    public static function emergency($message,array $content = array(),$module = '')
    {
        #$level = SEASLOG_EMERGENCY
        $message = self::getIpUri().$message;
        if($module !== ''){
            $module = $_SERVER['SERVER_NAME'].'/'.$module;
        }
        \SeasLog::emergency($message,$content,$module);
    }
 
    /**
     * 通用日志方法
     * @param $level
     * @param $message
     * @param array $content
     * @param string $module
     */
    public static function log($level,$message,array $content = array(),$module = '')
    {
        $message = self::getIpUri().$message;
        if($module !== ''){
            $module = $_SERVER['SERVER_NAME'].'/'.$module;
        }
        \SeasLog::log($level,$message,$content,$module);
    }
}