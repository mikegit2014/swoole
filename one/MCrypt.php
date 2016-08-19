<?php
namespace Tool\qrcode;
/**
 * SHA1 class
 *
 * 计算公众平台的消息签名接口.
 */
class SHA1
{
    /**
     * 用SHA1算法生成安全签名
     * @param string $token 签名秘钥
     * @param string $timestamp 时间戳
     * @param string $nonce 随机字符串
     * @param string $encrypt 密文消息
     */
    public function getSHA1($token, $timestamp, $nonce, $encrypt_msg)
    {
        $array = array('encrypt'=>$encrypt_msg,'timestamp'=>$timestamp,'nonce'=>$nonce);
        ksort($array);
        foreach ($array as $k => $v) {
            if(null != $v && "null" != $v && "sign" != $k){
                if($urlencode){
                    $v = urlencode($v);
                }
                $buff .= $k . "=" .$v ."&";
            }
        }
        $buff .= 'signkey='.$token;
        return strtoupper(sha1($buff));
    }

}

class MCrypt {
    private $key;
    private $signkey;
    private $appid;
    private $iv;
    function __construct($key,$iv,$signkey,$appid) {
        $this->key = $key;
        $this->signkey = $signkey;
        $this->appid = $appid;
        $this->iv = $iv;
    }
    /**
     * @param $data string 要加密的字符串
     * @param $timestamp string 时间戳，可以自己生成，也可以用URL参数的timestamp
     * @param $nonce string 随机串，可以自己生成，也可以用URL参数的nonce
     * @param 返回加密和签名后的数组,
     */
    function encrypt($data, $timestamp = null, $nonce) {
        //获得16位随机字符串，填充到明文之前
        $random = $this->getRandomStr();
        $data = $random . $data . $this->appid;
        $data = base64_encode($data);
        $encrypted = mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $this->key, $data, MCRYPT_MODE_CBC, $this->iv);
        $encrypted = base64_encode($encrypted); 
        /*$td = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_CBC, '');
        $iv = substr($this->key, 0, 16);
        mcrypt_generic_init($td, $iv, $iv);
        $block = mcrypt_get_block_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
        $pad = $block - (strlen($data) % $block);
        $data .= str_repeat(chr($pad), $pad);
        $encrypted = mcrypt_generic($td, $data);
        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);
        $encrypted = base64_encode($encrypted);*/
        if(empty($timestamp)){
            $timestamp = time();
        }

        //生成安全签名
        $sha1 = new SHA1;
        $signature = $sha1->getSHA1($this->signkey, $timestamp, $nonce, $encrypted);

        $encryptMsg = array('encrypt'=>$encrypted,'sign'=>$signature,'timestamp'=>$timestamp,'nonce'=>$nonce);

        return $encryptMsg;
    }
    /**
     * @param $msgSignature string 签名串，对应URL参数的msg_signature
     * @param $timestamp string 时间戳 对应URL参数的timestamp
     * @param $nonce string 随机串，对应URL参数的nonce
     * @param $encrypt_str string密文，对应POST请求的数据
     *
     * @return string 返回解密后的数据
     */
    function decrypt($msgSignature, $timestamp = null, $nonce, $encrypt_str) { 

        if (empty($timestamp)) {
            $timestamp = time();
        }

        //验证安全签名
        $sha1 = new SHA1;
        $signature = $sha1->getSHA1($this->signkey, $timestamp, $nonce, $encrypt_str);
        if ($signature != $msgSignature) {
            return -100;
        }

        $text = base64_decode($encrypt_str);
        $str = mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $this->key, $text, MCRYPT_MODE_CBC, $this->iv);
        $str = base64_decode($str);
        /*$td = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_CBC, '');
        mcrypt_generic_init($td, $iv, $iv);
        $str = mdecrypt_generic($td, $text);
        $block = mcrypt_get_block_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
        mcrypt_generic_deinit($td);
        mcrypt_module_close($td); 
        $str = $this->strippadding($str);*/
        $content = substr($str, 16, strlen($str));
        $from_appid = substr($content, -10);
        $content = substr($content, 0, strlen($content)-10);
        // $content = stripcslashes(trim($content,'"'));
        if($from_appid !== $this->appid){
            return -200;
        }
        return $content;
    }
    private function strippadding($string) {
        $slast = ord(substr($string, -1));
        $slastc = chr($slast);
        $pcheck = substr($string, -$slast);
        if (preg_match("/$slastc{" . $slast . "}/", $string)) {
            $string = substr($string, 0, strlen($string) - $slast);
            return $string;
        } else {
            return false;
        }
    }
    /**
     * 随机生成16位字符串
     * @return string 生成的字符串
     */
    function getRandomStr()
    {
        $str = "";
        $str_pol = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz";
        $max = strlen($str_pol) - 1;
        for ($i = 0; $i < 16; $i++) {
            $str .= $str_pol[mt_rand(0, $max)];
        }
        return $str;
    }
    //对象转数组
    function object_to_array($obj){
        $_arr = is_object($obj) ? get_object_vars($obj) : $obj;
        foreach ($_arr as $key => $val)
        {
            $val = (is_array($val) || is_object($val)) ? object_to_array($val) : $val;
            $arr[$key] = $val;
        }
        return $arr;
    }
}

