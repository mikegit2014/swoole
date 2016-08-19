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
    
    function get($name)
    {
    	$result = $this->http_get("/?charset=".$this->charset."&auth=".$this->auth."&name=".$name."&opt=get");
		if ($result["data"] == "HTTPSQS_ERROR" || $result["data"] == false) {
			return false;
		}
        return $result["data"];
    }
	
    function gets($name)
    {
    	$result = $this->http_get("/?charset=".$this->charset."&auth=".$this->auth."&name=".$name."&opt=get");
		if ($result["data"] == "HTTPSQS_ERROR" || $result["data"] == false) {
			return false;
		}
        return $result;
    }	
	
    function status($name)
    {
    	$result = $this->http_get("/?charset=".$this->charset."&auth=".$this->auth."&name=".$name."&opt=status");
		if ($result["data"] == "HTTPSQS_ERROR" || $result["data"] == false) {
			return false;
		}
        return $result["data"];
    }
	
    function view($name, $pos)
    {
    	$result = $this->http_get("/?charset=".$this->charset."&auth=".$this->auth."&name=".$name."&opt=view&pos=".$pos);
		if ($result["data"] == "HTTPSQS_ERROR" || $result["data"] == false) {
			return false;
		}
        return $result["data"];
    }
	
    function reset($name)
    {
    	$result = $this->http_get("/?charset=".$this->charset."&auth=".$this->auth."&name=".$name."&opt=reset");
		if ($result["data"] == "HTTPSQS_RESET_OK") {
			return true;
		}
        return false;
    }
	
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