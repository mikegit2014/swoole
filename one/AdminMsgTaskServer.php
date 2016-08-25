<?php
class AdminMsgTaskServer
{
    private $serv;
    private $redis;
    private $jp_app_key;
    private $jp_master_secret;
    private $callbackUrl;
    public function __construct() {
        $this->serv = new swoole_server("0.0.0.0", 9501);
        $this->serv->set(array(
            'ractor_num'    => 2,    //主进程中线程数量
            'worker_num' => 2,   //一般设置为服务器CPU数的1-4倍
            'daemonize' => false,   //是否守护进程
            'max_request' => 10000,
            'dispatch_mode' => 2,  //1平均分配，2按FD取摸固定分配，3抢占式分配，默认为取模(dispatch=2)'
            'debug_mode'=> true,
            'task_worker_num' => 2,  //task进程的数量 防止超过极光推送的600/min的限制
            "task_ipc_mode " => 3 ,  //使用消息队列通信，并设置为争抢模式
            "log_file" => "./MsgTaskSwoole.log" ,//日志
        ));
        //需要根据环境更改
        $redisIp = '172.16.0.126';
        $redisPort = 6379;
        $this->jp_app_key = 'bb8a38ffc8b122079e71495b';
		$this->jp_master_secret = 'a7bfe23deee35c6d983704e4';
        $this->callbackUrl = 'http://dianlfbradmin.bongv.com/Api/MessageCallBack/writeToBase';

        include_once('./RedisCluster.php');
        $this->redis = new RedisCluster();
        $this->redis->connect(array('host'=>$redisIp,'port'=>$redisPort));
	    $this->serv->on('Start', array($this, 'onStart'));
        $this->serv->on('Connect', array($this, 'onConnect'));
        $this->serv->on('Receive', array($this, 'onReceive'));
        // bind callback
        $this->serv->on('Task', array($this, 'onTask'));
        $this->serv->on('Finish', array($this, 'onFinish'));
        $this->serv->start();
    }
    public function onStart( $serv ) {
        echo "start\n";
        $this->write_log('swoole--start');
    }
    public function onConnect( $serv, $fd, $from_id ) {
        echo "connect\n";
        $this->write_log("Client {$fd} connect  form_id {$from_id}");
    }
    public function onReceive( $serv, $fd, $from_id, $data ) {
        // $this->write_log("server Receive data {$data}");
        $serv->task( $data ); 
    }
    public function onTask($serv,$task_id,$from_id, $data) {
        if( $data == 'begin'){
            $data = $this->redis->lpop('messageTask');
            if(!$data){
                return;
            }
        }
        $this->write_log("The Task exce data {$data}");
        $execdata = json_decode( $data,true );
        if( $execdata['msgid'] ){
            $jMsg_id = $this->JPushMsg( $execdata );
            if($jMsg_id){
                $returnArr['msgid'] = $execdata['msgid'];
                $returnArr['jpushMsgId'] = $jMsg_id;
                $returnArr['key'] = $execdata['key'];
                $returnArr['sendtime'] = time();
                $this->redis->rpush('messageTaskResult',json_encode($returnArr));
            }else{
                $this->write_log("JPush No msg_id exit task");
                return true;
            }
            $next = $this->redis->lpop('messageTask');
            if($next){
                $client = new \swoole_client(SWOOLE_SOCK_TCP);
                if(!$client->connect("127.0.0.1",9501,1)){
                    $this->write_log("Swoole Connect Error ");
                }
                $sendRes = $client->send( $next );
                $this->write_log("The Task send data {$next}");
            }else{
                $this->write_log("任务执行完毕");
                return true;
            }
        }else{
            $this->write_log("参数格式错误");
        }
    }
    public function onFinish($serv,$task_id, $data) {
        //Redis数据读取完成后,通知店立方服务器
        if($data){
            $this->httpGet();
        }
    }
    /* $data is array
	 * return is array
	*/
    protected function JPushMsg( $pushArr ){
    	include_once('./JPush.php');
    	$jpush = new JPush( $this->jp_app_key, $this->jp_master_secret );
    	$pData = array('msgid' => $pushArr['msgid']);
        $device = $pushArr['device'];
        $title = $pushArr['title'];
    	$jRes = $jpush
            ->setPlatform('ios', 'android')
            ->addAlias( $device )
            ->addAndroidNotification($title, $title, 1, $pData)
            ->addIosNotification($title, 'ClearF.aif', '+1', true, 'category', $pData)
            ->setMessage($title, $title, 'type', $pData)
            ->setOptions(100000, 3600, null, true)
            ->send();
        /*{"msg_id":1556852227,"error":{"message":"cannot find user by this audience","code":1011},"http_code":400}*/
        $this->write_log("Jpush exec Result {$jRes}");
        $tmpArr = json_decode($jRes,true);
        if(array_key_exists('msg_id', $tmpArr)){
        	return $tmpArr['msg_id'];
        }else{
        	return false;
        }
    }
    protected function write_log( $msg ) {
	    $logFile = './messageTaskLogs-'.date ( 'Y-m-d' ) . '.log';
	    if(is_array($msg)){
	        $msg_info = '['.date ( 'Y-m-d H:i:s' ).']' . ' >>> '.stripslashes(var_export($msg, true));
	    }else{
	        $msg_info = '['.date ( 'Y-m-d H:i:s' ).']' . ' >>> ' . $msg . "\r\n";
	    }

	    file_put_contents ( $logFile, $msg_info, FILE_APPEND );
	}
    //通知店立方,把数据写入数据库中
    protected function httpGet(){
        $backurl = $this->callbackUrl.'/redisKey/messageTaskResult';
        $this->write_log("通知店立方服务器--{$backurl}");
        $curlObj = curl_init();    //初始化curl，
        curl_setopt($curlObj, CURLOPT_URL, $backurl);   //设置网址
        curl_setopt($curlObj, CURLOPT_RETURNTRANSFER, 1);  //将curl_exec的结果返回
        curl_setopt($curlObj, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curlObj, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($curlObj, CURLOPT_HEADER, 0);         //是否输出返回头信息
        $response = curl_exec($curlObj);   //执行
        curl_close($curlObj);          //关闭会话
        return $response;
    }
}
$server = new AdminMsgTaskServer();
