<?php
class Server
{
    private $serv;
    public function __construct() {
        $this->serv = new swoole_server("0.0.0.0", 9501);
        $this->serv->set(array(
            'ractor_num'    => 2,    //主进程中线程数量
            'worker_num' => 4,   //一般设置为服务器CPU数的1-4倍
            'daemonize' => false,   //是否守护进程
            'max_request' => 10000,
            'dispatch_mode' => 2,  //1平均分配，2按FD取摸固定分配，3抢占式分配，默认为取模(dispatch=2)'
            'debug_mode'=> 1,
            'task_worker_num' => 10,  //task进程的数量
            "task_ipc_mode " => 3 ,  //使用消息队列通信，并设置为争抢模式
            "log_file" => "./MailSwoole.log" ,//日志
        ));
	    $this->serv->on('Start', array($this, 'onStart'));
        $this->serv->on('Connect', array($this, 'onConnect'));
        $this->serv->on('Receive', array($this, 'onReceive'));
        // bind callback
        $this->serv->on('Task', array($this, 'onTask'));
        $this->serv->on('Finish', array($this, 'onFinish'));
        $this->serv->start();
    }
    public function onStart( $serv ) {
        echo "Start\n";
    }
    public function onConnect( $serv, $fd, $from_id ) {
        echo "Client {$fd} connect  form_id {$from_id}\n";
    }
    public function onReceive( swoole_server $serv, $fd, $from_id, $data ) {
    	if($data){
    	   file_put_contents ( './serverReceive.log',"1\n", FILE_APPEND );
    	}
    	$tmp_arr = json_decode ($data,true);
    	$tmp_arr['fd'] = $fd;
        $serv->task( json_encode($tmp_arr) ); 
    }
    public function onTask($serv,$task_id,$from_id, $data) {
    	if($data){
    		file_put_contents ( './serverTask.log',"2\n", FILE_APPEND );
    	}
        $array = json_decode( $data , true );
    	$param = json_encode( $array['param'] );
    	//$param = '';
	    echo "task_id {$task_id}, request_url {$array['url']}, request_param {$param} \n";
        if ($array['url']) {
            $curl_res = $this->httpGet( $array['url'] , $array['param']  );
    	    $array['status'] = $curl_res;
    	    $return = json_encode($array);
            $serv->send($array['fd'],$return);
    	    return "{$return} ";
        }else{
	       file_put_contents ( './serverNoTask.log',$data."\n", FILE_APPEND );
	    }
    }
    public function onFinish($serv,$task_id, $data) {
        echo "Task {$task_id} finishn";
        echo "Result: {$data}\n";
    	if($data){
    	   file_put_contents ( './serverFiiTask.log',"3 \n", FILE_APPEND );
    	}
    	$msg = "Task_id {$task_id} exec Task result {$data} \n ";
    	include_once('./RedisCluster.php');
        $redis = new RedisCluster();
        $redis->connect(array('host'=>'172.16.0.126','port'=>6379));
    	$redis->rpush('serverTaskFinish',$msg);
    	file_put_contents ( './serverFinish.log',$data."\n", FILE_APPEND );
        //$serv->send(1,"Task_id {$task_id} exec Task result {$data}");
    }
    protected function httpGet($url,$data){
	    return array('status'=>rand(1,1000),'msg'=>'请求curl执行结果');
        if ($data) {
            $url .='?'.http_build_query($data) ;
        }
        $curlObj = curl_init();    //初始化curl，
        curl_setopt($curlObj, CURLOPT_URL, $url);   //设置网址
        curl_setopt($curlObj, CURLOPT_RETURNTRANSFER, 1);  //将curl_exec的结果返回
        curl_setopt($curlObj, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curlObj, CURLOPT_SSL_VERIFYHOST, FALSE);   
        curl_setopt($curlObj, CURLOPT_HEADER, 0);         //是否输出返回头信息
        $response = curl_exec($curlObj);   //执行
        curl_close($curlObj);          //关闭会话
        return $response;
    }
}
$server = new Server();
