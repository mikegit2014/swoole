<?php
/*
----------------------------------------------------------------------------------------------------------------
HTTP Simple Queue Service - httpsqs client class for PHP v1.2

Author: Zhang Yan (http://blog.s135.com), E-mail: net@s135.com
This is free software, and you are welcome to modify and redistribute it under the New BSD License
----------------------------------------------------------------------------------------------------------------
Useage:
<?php
......
include_once("httpsqs_client.php");
$httpsqs = new httpsqs;

//http connect without Keep-Alive
$result = $httpsqs->put($name, $data); //1. PUT text message into a queue. If PUT successful, return boolean: true. If an error occurs, return boolean: false. If queue full, return text: HTTPSQS_PUT_END
$result = $httpsqs->get($name); //2. GET text message from a queue. Return the queue contents. If there is no unread queue message, return text: HTTPSQS_GET_END
$result = $httpsqs->gets($name); //3. GET text message and pos from a queue. Return example: array("pos" => 7, "data" => "text message"). If there is no unread queue message, return: array("pos" => 0, "data" => "HTTPSQS_GET_END")
$result = $httpsqs->status($name); //4. View queue status
$result = $httpsqs->view($name, $pos); //5. View the contents of the specified queue pos (id). Return the contents of the specified queue pos.
$result = $httpsqs->reset($name); //6. Reset the queue. If reset successful, return boolean: true. If an error occurs, return boolean: false
$result = $httpsqs->maxqueue($name, $num); //7. Change the maximum queue length of per-queue. If change the maximum queue length successful, return boolean: true. If  it be cancelled, return boolean: false

//http pconnect with Keep-Alive (Very fast in PHP FastCGI mode & Command line mode)
$result = $httpsqs->pput($name, $data); //1. PUT text message into a queue. If PUT successful, return boolean: true. If an error occurs, return boolean: false.  If queue full, return text: HTTPSQS_PUT_END
$result = $httpsqs->pget($name); //2. GET text message from a queue. Return the queue contents. If there is no unread queue message, return text: HTTPSQS_GET_END
$result = $httpsqs->pgets($name); //3. GET text message and pos from a queue. Return example: array("pos" => 7, "data" => "text message"). If there is no unread queue message, return: array("pos" => 0, "data" => "HTTPSQS_GET_END")
$result = $httpsqs->pstatus($name); //4. View queue status
$result = $httpsqs->pview($name, $pos); //5. View the contents of the specified queue pos (id). Return the contents of the specified queue pos.
$result = $httpsqs->preset($name); //6. Reset the queue. If reset successful, return boolean: true. If an error occurs, return boolean: false
$result = $httpsqs->pmaxqueue($name, $num); //7. Change the maximum queue length of per-queue. If change the maximum queue length successful, return boolean: true. If  it be cancelled, return boolean: false
?>
----------------------------------------------------------------------------------------------------------------
*/
namespace Tool\qrcode;
class Httpsqs  {
    private $host;
    private $port;
    private $charset;
    private $auth;
    public function __construct($host, $port, $auth = '',$charset = 'utf-8')
    {
        $this->host = $host;
        $this->port = $port;
        $this->auth = $auth;
        $this->charset = $charset;
    }    
    function http_get($query)
    {   
        $host = $this->host;
        $port = $this->port;
        $fp = fsockopen($host, $port, $errno, $errstr, 1);
        if (!$fp)
        {
            return false;
        }
        $out = "GET ${query} HTTP/1.1\r\n";
        $out .= "Host: ${host}\r\n";
        $out .= "Connection: close\r\n";
        $out .= "\r\n";
        fwrite($fp, $out);
        $line = trim(fgets($fp));
        $header .= $line;
        list($proto, $rcode, $result) = explode(" ", $line);
        $len = -1;
        while (($line = trim(fgets($fp))) != "")
        {
            $header .= $line;
            if (strstr($line, "Content-Length:"))
            {
                list($cl, $len) = explode(" ", $line);
            }
            if (strstr($line, "Pos:"))
            {
                list($pos_key, $pos_value) = explode(" ", $line);
            }			
            if (strstr($line, "Connection: close"))
            {
                $close = true;
            }
        }
        if ($len < 0)
        {
            return false;
        }
        $body = @fread($fp, $len);
        if ($close) fclose($fp);
		$result_array["pos"] = (int)$pos_value;
		$result_array["data"] = $body;
        return $result_array;
    }

    function http_post($query, $body)
    {
        $host = $this->host;
        $port = $this->port;
        $fp = fsockopen($host, $port, $errno, $errstr, 1);
        if (!$fp)
        {
            return false;
        }
        $out = "POST ${query} HTTP/1.1\r\n";
        $out .= "Host: ${host}\r\n";
        $out .= "Content-Length: " . strlen($body) . "\r\n";
        $out .= "Connection: close\r\n";
        $out .= "\r\n";
        $out .= $body;
        fwrite($fp, $out);
        $line = trim(fgets($fp));
        $header .= $line;
        list($proto, $rcode, $result) = explode(" ", $line);
        $len = -1;
        while (($line = trim(fgets($fp))) != "")
        {
            $header .= $line;
            if (strstr($line, "Content-Length:"))
            {
                list($cl, $len) = explode(" ", $line);
            }
            if (strstr($line, "Pos:"))
            {
                list($pos_key, $pos_value) = explode(" ", $line);
            }			
            if (strstr($line, "Connection: close"))
            {
                $close = true;
            }
        }
        if ($len < 0)
        {
            return false;
        }
        $body = @fread($fp, $len);
        if ($close) fclose($fp);
		$result_array["pos"] = (int)$pos_value;
		$result_array["data"] = $body;
        return $result_array;
    }
	
    function http_pget($query)
    {
        $host = $this->host;
        $port = $this->port;
        $fp = pfsockopen($host, $port, $errno, $errstr, 1);
        if (!$fp)
        {
            return false;
        }
        $out = "GET ${query} HTTP/1.1\r\n";
        $out .= "Host: ${host}\r\n";
        $out .= "Connection: Keep-Alive\r\n";
        $out .= "\r\n";
        fwrite($fp, $out);
        $line = trim(fgets($fp));
        $header .= $line;
        list($proto, $rcode, $result) = explode(" ", $line);
        $len = -1;
        while (($line = trim(fgets($fp))) != "")
        {
            $header .= $line;
            if (strstr($line, "Content-Length:"))
            {
                list($cl, $len) = explode(" ", $line);
            }
            if (strstr($line, "Pos:"))
            {
                list($pos_key, $pos_value) = explode(" ", $line);
            }			
            if (strstr($line, "Connection: close"))
            {
                $close = true;
            }
        }
        if ($len < 0)
        {
            return false;
        }
        $body = @fread($fp, $len);
        if ($close) fclose($fp);
		$result_array["pos"] = (int)$pos_value;
		$result_array["data"] = $body;
        return $result_array;
    }

    function http_ppost($query, $body)
    {
        $host = $this->host;
        $port = $this->port;
        $fp = pfsockopen($host, $port, $errno, $errstr, 1);
        if (!$fp)
        {
            return false;
        }
        $out = "POST ${query} HTTP/1.1\r\n";
        $out .= "Host: ${host}\r\n";
        $out .= "Content-Length: " . strlen($body) . "\r\n";
        $out .= "Connection: Keep-Alive\r\n";
        $out .= "\r\n";
        $out .= $body;
        fwrite($fp, $out);
        $line = trim(fgets($fp));
        $header .= $line;
        list($proto, $rcode, $result) = explode(" ", $line);
        $len = -1;
        while (($line = trim(fgets($fp))) != "")
        {
            $header .= $line;
            if (strstr($line, "Content-Length:"))
            {
                list($cl, $len) = explode(" ", $line);
            }
            if (strstr($line, "Pos:"))
            {
                list($pos_key, $pos_value) = explode(" ", $line);
            }			
            if (strstr($line, "Connection: close"))
            {
                $close = true;
            }
        }
        if ($len < 0)
        {
            return false;
        }
        $body = @fread($fp, $len);
        if ($close) fclose($fp);
		$result_array["pos"] = (int)$pos_value;
		$result_array["data"] = $body;
        return $result_array;
    }
    /*  
    1. 将文本信息放入一个队列（注意：如果要放入队列的PHP变量是一个数组，需要事先使用序列化、json_encode等函数转换成文本） 
        如果入队列成功，返回布尔值：true  
        如果入队列失败，返回布尔值：false  
    */ 
    function put($name, $data)
    {
    	$result = $this->http_post("/?charset=".$this->charset."&auth=".$this->auth."&name=".$name."&opt=put", $data);
		if ($result["data"] == "HTTPSQS_PUT_OK") {
			return true;
		} else if ($result["data"] == "HTTPSQS_PUT_END") {
			return $result["data"];
		}
		return false;
    }
    /*  
    2. 从一个队列中取出文本信息 
        返回该队列的内容 
        如果没有未被取出的队列，则返回文本信息：HTTPSQS_GET_END 
        如果发生错误，返回布尔值：false  
    */
    function get($name)
    {
    	$result = $this->http_get("/?charset=".$this->charset."&auth=".$this->auth."&name=".$name."&opt=get");
		if ($result["data"] == "HTTPSQS_ERROR" || $result["data"] == false) {
			return false;
		}
        return $result["data"];
    }
	/*  
    3. 从一个队列中取出文本信息和当前队列读取点Pos 
        返回数组示例：array("pos" => 7, "data" => "text message") 
        如果没有未被取出的队列，则返回数组：array("pos" => 0, "data" => "HTTPSQS_GET_END") 
        如果发生错误，返回布尔值：false 
    */
    function gets($name)
    {
    	$result = $this->http_get("/?charset=".$this->charset."&auth=".$this->auth."&name=".$name."&opt=get");
		if ($result["data"] == "HTTPSQS_ERROR" || $result["data"] == false) {
			return false;
		}
        return $result;
    }	
	/*  
    4. 查看队列状态（普通方式） 
    */ 
    function status($name)
    {
    	$result = $this->http_get("/?charset=".$this->charset."&auth=".$this->auth."&name=".$name."&opt=status");
		if ($result["data"] == "HTTPSQS_ERROR" || $result["data"] == false) {
			return false;
		}
        return $result["data"];
    }
	/*  
    6. 查看指定队列位置点的内容 
        返回指定队列位置点的内容。 
    */ 
    function view($name, $pos)
    {
    	$result = $this->http_get("/?charset=".$this->charset."&auth=".$this->auth."&name=".$name."&opt=view&pos=".$pos);
		if ($result["data"] == "HTTPSQS_ERROR" || $result["data"] == false) {
			return false;
		}
        return $result["data"];
    }
	/*  
    7. 重置指定队列 
        如果重置队列成功，返回布尔值：true  
        如果重置队列失败，返回布尔值：false  
    */ 
    function reset($name)
    {
    	$result = $this->http_get("/?charset=".$this->charset."&auth=".$this->auth."&name=".$name."&opt=reset");
		if ($result["data"] == "HTTPSQS_RESET_OK") {
			return true;
		}
        return false;
    }
	/*  
    8. 更改指定队列的最大队列数量 
       如果更改成功，返回布尔值：true 
       如果更改操作被取消，返回布尔值：false 
    */
    function maxqueue($name, $num)
    {
    	$result = $this->http_get("/?charset=".$this->charset."&auth=".$this->auth."&name=".$name."&opt=maxqueue&num=".$num);
		if ($result["data"] == "HTTPSQS_MAXQUEUE_OK") {
			return true;
		}
        return false;
    }
	
    function pput($name, $data)
    {
    	$result = $this->http_ppost("/?charset=".$this->charset."&auth=".$this->auth."&name=".$name."&opt=put", $data);
		if ($result["data"] == "HTTPSQS_PUT_OK") {
			return true;
		} else if ($result["data"] == "HTTPSQS_PUT_END") {
			return $result["data"];
		}
		return false;
    }
    
    function pget($name)
    {
    	$result = $this->http_pget("/?charset=".$this->charset."&auth=".$this->auth."&name=".$name."&opt=get");
		if ($result["data"] == "HTTPSQS_ERROR" || $result["data"] == false) {
			return false;
		}
        return $result["data"];
    }
	
    function pgets($name)
    {
    	$result = $this->http_pget("/?charset=".$this->charset."&auth=".$this->auth."&name=".$name."&opt=get");
		if ($result["data"] == "HTTPSQS_ERROR" || $result["data"] == false) {
			return false;
		}
        return $result;
    }	
	
    function pstatus($name)
    {
    	$result = $this->http_pget("/?charset=".$this->charset."&auth=".$this->auth."&name=".$name."&opt=status");
		if ($result["data"] == "HTTPSQS_ERROR" || $result["data"] == false) {
			return false;
		}
        return $result["data"];
    }
	
    function pview($name, $pos)
    {
    	$result = $this->http_pget("/?charset=".$this->charset."&auth=".$this->auth."&name=".$name."&opt=view&pos=".$pos);
		if ($result["data"] == "HTTPSQS_ERROR" || $result["data"] == false) {
			return false;
		}
        return $result["data"];
    }
	
    function preset($name)
    {
    	$result = $this->http_pget("/?charset=".$this->charset."&auth=".$this->auth."&name=".$name."&opt=reset");
		if ($result["data"] == "HTTPSQS_RESET_OK") {
			return true;
		}
        return false;
    }
	
    function pmaxqueue($name, $num)
    {
    	$result = $this->http_pget("/?charset=".$this->charset."&auth=".$this->auth."&name=".$name."&opt=maxqueue&num=".$num);
		if ($result["data"] == "HTTPSQS_MAXQUEUE_OK") {
			return true;
		}
        return false;
    }
}
?>