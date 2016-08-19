<?php
class Server
{
    private $serv;
    public function __construct() {
        $this->serv = new swoole_server("0.0.0.0", 9501);
        $this->serv->set(array(
            'ractor_num'    => 1,    //主进程中线程数量
            'worker_num' => 2,   //一般设置为服务器CPU数的1-4倍
            'daemonize' => false,   //是否守护进程
            'max_request' => 10000,
            'dispatch_mode' => 2,  //1平均分配，2按FD取摸固定分配，3抢占式分配，默认为取模(dispatch=2)'
            'debug_mode'=> 1,
            'task_worker_num' => 4,  //task进程的数量
            "task_ipc_mode " => 3 ,  //使用消息队列通信，并设置为争抢模式
            "log_file" => "./Logs/MailSwoole.log" ,//日志
        ));
        $this->serv->on('Receive', array($this, 'onReceive'));
        // bind callback
        $this->serv->on('Task', array($this, 'onTask'));
        $this->serv->on('Finish', array($this, 'onFinish'));
        $this->serv->start();
    }
    public function onReceive( swoole_server $serv, $fd, $from_id, $data ) {
        //echo "Get Message From Client {$fd}:{$data}n";
        // send a task to task worker.
        $serv->task( $data );
    }
    public function onTask($serv,$task_id,$from_id, $data) {
        $array = json_decode( $data , true );
        if ($array['url']) {
            return $this->httpGet( $array['url'] , $array['param']  );
        }
    }
    public function onFinish($serv,$task_id, $data) {
        //echo "Task {$task_id} finishn";
        //echo "Result: {$data}n";
    }
    protected function httpGet($url,$data){
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