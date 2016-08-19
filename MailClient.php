<?php
class Client
{
    private $client;
    public function __construct() {
        $this->client = new swoole_client(SWOOLE_SOCK_TCP);
    }
    public function connect() {
        if( !$this->client->connect("127.0.0.1", 9501 , 1) ) {
            echo "Connect Error";
        }
        include_once('./RedisCluster.php');
        $redis = new RedisCluster();
        $redis->connect();
        $len = $redis->llen('orderTask');
        file_put_contents ( './swoole.log',$len, FILE_APPEND );

        $this->client->send( $json_data );
    }
}
$client = new Client();
$client->connect();