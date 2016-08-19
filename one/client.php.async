<?php
class Client
{
	private $client;
 	private $redis; 
	public function __construct() {
   	$this->client = new swoole_client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_ASYNC);
    $this->client->on('Connect', array($this, 'onConnect'));
    $this->client->on('Receive', array($this, 'onReceive'));
    $this->client->on('Close', array($this, 'onClose'));
    $this->client->on('Error', array($this, 'onError'));
	  include_once('./RedisCluster.php');
    $this->redis = new RedisCluster();
    $this->redis->connect(array('host'=>'172.16.0.126','port'=>6379));
	}
	
	public function connect() {
		$fp = $this->client->connect("127.0.0.1", 9501 , 1);
		if( !$fp ) {
			echo "Error: {$fp->errMsg}[{$fp->errCode}]\n";
			return;
		}
	}
	public function onReceive( $cli, $data ) {
    $arr = json_decode($data,true);
    $res = $this->redis->incr('page');
    if($arr['status'] > 1){
  	  file_put_contents ( './clientFinsh.log',$data."\n", FILE_APPEND );
    }else{
      file_put_contents ( './clientNoFinish.log',$data."\n", FILE_APPEND );
    }
    $xh = $this->redis->get('page');
    if(!empty($data)){
      $json = $this->redis->lranges('orderTask',0,1);
      $json_res = $json[0];
      $arr = json_decode($json_res,true);
      $last = $arr[$xh];
      if(!empty($last))
        $this->send( json_encode($last));
    }
  }
  public function onConnect( $cli) {
	  echo "onConnect\n";
    //$len =  $redis->llen('orderTask');
    $json = $this->redis->lranges('orderTask',0,1);
    $json_res = $json[0];
    $arr = json_decode($json_res,true);
	  $this->redis->set('page',0);
	  $this->send( json_encode($arr[0]));
  }
  public function onClose( $cli) {
    echo "Client close connection\n";
  }
  public function onError() {
  }
  public function send($data) {
  	$this->client->send( $data );
  }
  public function isConnected() {
  	return $this->client->isConnected();
  }
}
$cli = new Client();
$cli->connect();
