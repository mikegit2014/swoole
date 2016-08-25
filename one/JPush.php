<?php
class JPush {
    private static $EFFECTIVE_DEVICE_TYPES = array('ios', 'android', 'winphone');
    private static $LIMIT_KEYS = array('X-Rate-Limit-Limit'=>'rateLimitLimit', 'X-Rate-Limit-Remaining'=>'rateLimitRemaining', 'X-Rate-Limit-Reset'=>'rateLimitReset');
    const PUSH_URL = 'https://api.jpush.cn/v3/push';
    const PUSH_VALIDATE_URL = 'https://api.jpush.cn/v3/push/validate';
    const DEFAULT_LOG_FILE = "./jpush.log";
    const REPORT_URL = 'https://report.jpush.cn/v3/received';
    const USER_AGENT = 'JPush-API-PHP-Client';
    const DISABLE_SOUND = "_disable_Sound";
    const DISABLE_BADGE = 0x10000;
    const CONNECT_TIMEOUT = 5;
    const READ_TIMEOUT = 30;
    const HTTP_GET = 'GET';
    const HTTP_POST = 'POST';
    const HTTP_DELETE = 'DELETE';
    const HTTP_PUT = 'PUT';

    private $platform;

    private $audience;
    private $tags;
    private $tagAnds;
    private $alias;
    private $registrationIds;

    private $notificationAlert;
    private $iosNotification;
    private $androidNotification;
    private $winPhoneNotification;
    private $smsMessage;
    private $message;
    private $options;
    private $retryTimes;

    /**
     * PushPayload constructor.
     * @param $client JPush
     */
    function __construct($appKey, $masterSecret, $logFile=self::DEFAULT_LOG_FILE) {
        if (is_null($appKey) || is_null($masterSecret)) {
            die("appKey and masterSecret must be set.");
        }
        if (!is_string($appKey) || !is_string($masterSecret)) {
            die("Invalid appKey or masterSecret");
        }
        $this->appKey = $appKey;
        $this->masterSecret = $masterSecret;

        $this->logFile = $logFile;
        $this->retryTimes = 3;
    }

    public function setPlatform($platform) {
        if (is_string($platform) && strcasecmp("all", $platform) === 0) {
            $this->platform = "all";
        } else {
            if (!is_array($platform)) {
                $platform = func_get_args();
                if (count($platform) <= 0) {
                    $this->log("setPlatform:Missing argument");
                }
            }

            $_platform = array();
            foreach($platform as $type) {
                $type = strtolower($type);
                if (!in_array($type, self::$EFFECTIVE_DEVICE_TYPES)) {
                    $this->log("setPlatform:Invalid device type: ".$type);
                }
                if (!in_array($type, $_platform)) {
                    array_push($_platform, $type);
                }
            }
            $this->platform = $_platform;
        }
        return $this;
    }

    public function setAudience($all) {
        if (strtolower($all) === 'all') {
            $this->addAllAudience();
        } else {
            $this->log("setAudience:Invalid audience value");
        }
    }

    public function addAllAudience() {
        $this->audience = "all";
        return $this;
    }

    public function addTag($tag) {
        if (is_null($this->tags)) {
            $this->tags = array();
        }

        if (is_array($tag)) {
            foreach($tag as $_tag) {
                if (!is_string($_tag)) {
                    $this->log("addTag:Invalid tag value");
                }
                if (!in_array($_tag, $this->tags)) {
                    array_push($this->tags, $_tag);
                }
            }
        } else if (is_string($tag)) {
            if (!in_array($tag, $this->tags)) {
                array_push($this->tags, $tag);
            }
        } else {
            $this->log("addTag:Invalid tag value");
        }

        return $this;

    }

    public function addTagAnd($tag) {
        if (is_null($this->tagAnds)) {
            $this->tagAnds = array();
        }

        if (is_array($tag)) {
            foreach($tag as $_tag) {
                if (!is_string($_tag)) {
                    $this->log("addTagAnd:Invalid tag_and value");
                }
                if (!in_array($_tag, $this->tagAnds)) {
                    array_push($this->tagAnds, $_tag);
                }
            }
        } else if (is_string($tag)) {
            if (!in_array($tag, $this->tagAnds)) {
                array_push($this->tagAnds, $tag);
            }
        } else {
            $this->log("addTagAnd:Invalid tag_and value");
        }

        return $this;
    }

    public function addAlias($alias) {
        if (is_null($this->alias)) {
            $this->alias = array();
        }

        if (is_array($alias)) {
            foreach($alias as $_alias) {
                if (!is_string($_alias)) {
                    $this->log("addAlias:Invalid alias value");
                }
                if (!in_array($_alias, $this->alias)) {
                    array_push($this->alias, $_alias);
                }
            }
        } else if (is_string($alias)) {
            if (!in_array($alias, $this->alias)) {
                array_push($this->alias, $alias);
            }
        } else {
             $this->log("addAlias:Invalid alias value");
        }

        return $this;
    }

    public function addRegistrationId($registrationId) {
        if (is_null($this->registrationIds)) {
            $this->registrationIds = array();
        }

        if (is_array($registrationId)) {
            foreach($registrationId as $_registrationId) {
                if (!is_string($_registrationId)) {
                    $this->log("addRegistrationId:Invalid registration_id value");
                }
                if (!in_array($_registrationId, $this->registrationIds)) {
                    array_push($this->registrationIds, $_registrationId);
                }
            }
        } else if (is_string($registrationId)) {
            if (!in_array($registrationId, $this->registrationIds)) {
                array_push($this->registrationIds, $registrationId);
            }
        } else {
            $this->log("addRegistrationId:Invalid registration_id value");
        }

        return $this;
    }

    public function setNotificationAlert($alert) {
        if (!is_string($alert)) {
            $this->log("setNotificationAlert:Invalid alert value");
        }
        $this->notificationAlert = $alert;
        return $this;
    }

    public function addIosNotification($alert=null, $sound=null, $badge=null, $content_available=null, $category=null, $extras=null) {
        $ios = array();

        if (!is_null($alert)) {
            if (!is_string($alert) && !is_array($alert)) {
                $this->log("addIosNotification:Invalid ios alert value");
            }
            $ios['alert'] = $alert;
        }

        if (!is_null($sound)) {
            if (!is_string($sound)) {
                $this->log("addIosNotification:Invalid ios sound value");
            }
            if ($sound !== self::DISABLE_SOUND) {
                $ios['sound'] = $sound;
            }
        } else {
            // 默认sound为''
            $ios['sound'] = '';
        }

        if (!is_null($badge)) {
            if (is_string($badge) && !preg_match("/^[+-]{1}[0-9]{1,3}$/", $badge)) {
                if (!is_int($badge)) {
                    $this->log("addIosNotification:Invalid ios badge value");
                }
            }
            if ($badge != self::DISABLE_BADGE) {
                $ios['badge'] = $badge;
            }
        } else {
            // 默认badge为'+1'
            $ios['badge'] = '+1';
        }

        if (!is_null($content_available)) {
            if (!is_bool($content_available)) {
                $this->log("addIosNotification:Invalid ios content-available value");
            }
            $ios['content-available'] = $content_available;
        }

        if (!is_null($category)) {
            if (!is_string($category)) {
                $this->log("addIosNotification:Invalid ios category value");
            }
            if (strlen($category)) {
                $ios['category'] = $category;
            }
        }

        if (!is_null($extras)) {
            if (!is_array($extras)) {
                $this->log("addIosNotification:Invalid ios extras value");
            }
            if (count($extras) > 0) {
                $ios['extras'] = $extras;
            }
        }

        if (count($ios) <= 0) {
            $this->log("addIosNotification:Invalid iOS notification");
        }

        $this->iosNotification = $ios;
        return $this;
    }

    public function addAndroidNotification($alert=null, $title=null, $builderId=null, $extras=null) {
        $android = array();

        if (!is_null($alert)) {
            if (!is_string($alert)) {
                $this->log("addAndroidNotification:Invalid android alert value");
            }
            $android['alert'] = $alert;
        }

        if (!is_null($title)) {
            if(!is_string($title)) {
                $this->log("addAndroidNotification:Invalid android title value");
            }
            if(strlen($title) > 0) {
                $android['title'] = $title;
            }
        }

        if (!is_null($builderId)) {
            if (!is_int($builderId)) {
                $this->log("addAndroidNotification:Invalid android builder_id value");
            }
            $android['builder_id'] = $builderId;
        }

        if (!is_null($extras)) {
            if (!is_array($extras)) {
                $this->log("addAndroidNotification:Invalid android extras value");
            }
            if (count($extras) > 0) {
                $android['extras'] = $extras;
            }
        }

        if (count($android) <= 0) {
            $this->log("addAndroidNotification:Invalid android notification");
        }

        $this->androidNotification = $android;
        return $this;
    }

    public function addWinPhoneNotification($alert=null, $title=null, $_open_page=null, $extras=null) {
        $winPhone = array();

        if (!is_null($alert)) {
            if (!is_string($alert)) {
                $this->log("addWinPhoneNotification:Invalid android notificatio");
            }
            $winPhone['alert'] = $alert;
        }

        if (!is_null($title)) {
            if (!is_string($title)) {
                $this->log("addWinPhoneNotification:Invalid winphone title notification");
            }
            if(strlen($title) > 0) {
                $winPhone['title'] = $title;
            }
        }

        if (!is_null($_open_page)) {
            if (!is_string($_open_page)) {
                $this->log("addWinPhoneNotification:Invalid winphone _open_page notification");
            }
            if (strlen($_open_page) > 0) {
                $winPhone['_open_page'] = $_open_page;
            }
        }

        if (!is_null($extras)) {
            if (!is_array($extras)) {
                $this->log("addWinPhoneNotification:Invalid winphone extras notification");
            }
            if (count($extras) > 0) {
                $winPhone['extras'] = $extras;
            }
        }

        if (count($winPhone) <= 0) {
            $this->log("addWinPhoneNotification:Invalid winphone notification");
        }

        $this->winPhoneNotification = $winPhone;
        return $this;
    }

    public function setSmsMessage($content, $delay_time) {
        $sms = array();
        if (is_null($content) || !is_string($content) || strlen($content) < 0 || strlen($content) > 480) {
            $this->log("setSmsMessage:Invalid sms content, sms content\'s length must in [0, 480]");
        } else {
            $sms['content'] = $content;
        }

        if (is_null($delay_time) || !is_int($delay_time) || $delay_time < 0 || $delay_time > 86400) {
            throw new InvalidArgumentException('');
            $this->log("setSmsMessage:Invalid sms delay time, delay time must in [0, 86400]");
        } else {
            $sms['delay_time'] = $delay_time;
        }

        $this->smsMessage = $sms;
        return $this;
    }


    public function setMessage($msg_content, $title=null, $content_type=null, $extras=null) {
        $message = array();

        if (is_null($msg_content) || !is_string($msg_content)) {
            $this->log("setMessage:Invalid message content");
        } else {
            $message['msg_content'] = $msg_content;
        }

        if (!is_null($title)) {
            if (!is_string($title)) {
                $this->log("setMessage:Invalid message title");
            }
            $message['title'] = $title;
        }

        if (!is_null($content_type)) {
            if (!is_string($content_type)) {
                $this->log("setMessage:Invalid message content type");
            }
            $message["content_type"] = $content_type;
        }

        if (!is_null($extras)) {
            if (!is_array($extras)) {
                $this->log("setMessage:Invalid message extras");
            }
            if (count($extras) > 0) {
                $message['extras'] = $extras;
            }
        }

        $this->message = $message;
        return $this;
    }

    public function setOptions($sendno=null, $time_to_live=null, $override_msg_id=null, $apns_production=null, $big_push_duration=null) {
        $options = array();

        if (!is_null($sendno)) {
            if (!is_int($sendno)) {
                $this->log("setOptions:Invalid option sendno");
            }
            $options['sendno'] = $sendno;
        } else {
            $options['sendno'] = $this->generateSendno();
        }

        if (!is_null($time_to_live)) {
            if (!is_int($time_to_live) || $time_to_live < 0 || $time_to_live > 864000) {
                $this->log("setOptions:Invalid option time to live, it must be a int and in [0, 864000]");
            }
            $options['time_to_live'] = $time_to_live;
        }

        if (!is_null($override_msg_id)) {
            if (!is_long($override_msg_id)) {
                $this->log("setOptions:Invalid option override msg id");
            }
            $options['override_msg_id'] = $override_msg_id;
        }

        if (!is_null($apns_production)) {
            if (!is_bool($apns_production)) {
                $this->log("setOptions:Invalid option apns production");
            }
            $options['apns_production'] = $apns_production;
        } else {
            $options['apns_production'] = false;
        }

        if (!is_null($big_push_duration)) {
            if (!is_int($big_push_duration) || $big_push_duration < 0 || $big_push_duration > 1440) {
                $this->log("setOptions:Invalid option big push duration, it must be a int and in [0, 1440]");
            }
            $options['big_push_duration'] = $big_push_duration;
        }

        $this->options = $options;
        return $this;
    }

    public function build() {
        $payload = array();

        // validate platform
        if (is_null($this->platform)) {
            $this->log("build:platform must be set");
        }
        $payload["platform"] = $this->platform;

        // validate audience
        $audience = array();
        if (!is_null($this->tags)) {
            $audience["tag"] = $this->tags;
        }
        if (!is_null($this->tagAnds)) {
            $audience["tag_and"] = $this->tagAnds;
        }
        if (!is_null($this->alias)) {
            $audience["alias"] = $this->alias;
        }
        if (!is_null($this->registrationIds)) {
            $audience["registration_id"] = $this->registrationIds;
        }

        if (is_null($this->audience) && count($audience) <= 0) {
            $this->log("build:audience must be set");
        } else if (!is_null($this->audience) && count($audience) > 0) {
            $this->log("build:you can't add tags/alias/registration_id/tag_and when audience='all'");
        } else if (is_null($this->audience)) {
            $payload["audience"] = $audience;
        } else {
            $payload["audience"] = $this->audience;
        }
        // validate notification
        $notification = array();

        if (!is_null($this->notificationAlert)) {
            $notification['alert'] = $this->notificationAlert;
        }

        if (!is_null($this->androidNotification)) {
            $notification['android'] = $this->androidNotification;
            if (is_null($this->androidNotification['alert'])) {
                if (is_null($this->notificationAlert)) {
                    $this->log("build:Android alert can not be null");
                } else {
                    $notification['android']['alert'] = $this->notificationAlert;
                }
            }
        }

        if (!is_null($this->iosNotification)) {
            $notification['ios'] = $this->iosNotification;
            if (is_null($this->iosNotification['alert'])) {
                if (is_null($this->notificationAlert)) {
                    $this->log("build:iOS alert can not be null");
                } else {
                    $notification['ios']['alert'] = $this->notificationAlert;
                }
            }
        }

        if (!is_null($this->winPhoneNotification)) {
            $notification['winphone'] = $this->winPhoneNotification;
            if (is_null($this->winPhoneNotification['alert'])) {
                if (is_null($this->winPhoneNotification)) {
                    $this->log("build:WinPhone alert can not be null");
                } else {
                    $notification['winphone']['alert'] = $this->notificationAlert;
                }
            }
        }

        if (count($notification) > 0) {
            $payload['notification'] = $notification;
        }

        if (count($this->message) > 0) {
            $payload['message'] = $this->message;
        }
        if (!array_key_exists('notification', $payload) && !array_key_exists('message', $payload)) {
            $this->log("build:notification and message can not all be null");
        }

        if (count($this->smsMessage)) {
            $payload['sms_message'] = $this->smsMessage;
        }

        if (count($this->options) > 0) {
            $payload['options'] = $this->options;
        } else {
            $this->setOptions();
            $payload['options'] = $this->options;
        }
        return $payload;
    }

    public function toJSON() {
        $payload = $this->build();
        return json_encode($payload);
    }

    public function printJSON() {
        echo $this->toJSON();
        return $this;
    }

    public function send() {
        $response = $this->_request(self::PUSH_URL, self::HTTP_POST, $this->toJSON());
        $this->log("Results " . json_encode($response));
        return $response;
        // return $this->__processResp($response);
    }

    public function validate() {
        $response = $this->_request(self::PUSH_VALIDATE_URL, self::HTTP_POST, $this->toJSON());
        $this->log("Results " . json_encode($response));
        return $response;
        // return $this->__processResp($response);
    }

    private function __processResp($response) {
        $this->log("Results " . json_encode($response));
        if($response['http_code'] === 200) {
            $body = array();
            $body['data'] = json_decode($response['body']);
            $headers = $response['headers'];
            if (is_array($headers)) {
                $limit = array();
                foreach (self::$LIMIT_KEYS as $key => $value) {
                    if (array_key_exists($key, $headers)) {
                        $limit[$value] = $headers[$key];
                    }
                }
                if (count($limit) > 0) {
                    $body['limit'] = (object)$limit;
                }
                return (object)$body;
            }
            return $body;
        } else {
            return $response;
        }
    }
    private function generateSendno() {
        return rand(100000, 4294967294);
    }
    /**
     * 发送HTTP请求
     * @param $url string 请求的URL
     * @param $method int 请求的方法
     * @param null $body String POST请求的Body
     * @param int $times 当前重试的册数
     * @return array
     * @throws APIConnectionException
     */
    public function _request($url, $method, $body=null, $times=1) {
        $this->log("Send " . $method . " " . $url . ", body:" . $body . ", times:" . $times);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        // 设置User-Agent
        curl_setopt($ch, CURLOPT_USERAGENT, self::USER_AGENT);
        // 连接建立最长耗时
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, self::CONNECT_TIMEOUT);
        // 请求最长耗时
        curl_setopt($ch, CURLOPT_TIMEOUT, self::READ_TIMEOUT);
        // 设置SSL版本 1=CURL_SSLVERSION_TLSv1, 不指定使用默认值,curl会自动获取需要使用的CURL版本
        // curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        // 如果报证书相关失败,可以考虑取消注释掉该行,强制指定证书版本
        //curl_setopt($ch, CURLOPT_SSL_CIPHER_LIST, 'TLSv1');
        // 设置Basic认证
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, $this->appKey . ":" . $this->masterSecret);
        // 设置Post参数
        if ($method === self::HTTP_POST) {
            curl_setopt($ch, CURLOPT_POST, true);
        } else if ($method === self::HTTP_DELETE || $method === self::HTTP_PUT || $method === self::HTTP_GET) {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        }
        if (!is_null($body)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        // 设置headers
        // $base64_auth_str = base64_encode("$this->appKey:$this->masterSecret");
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Connection: Keep-Alive',
            // "Authorization: Basic $base64_auth_str"
        ));

        // 执行请求
        $output = curl_exec($ch);
        // 解析Response
        $response = array();
        $errorCode = curl_errno($ch);
        if ($errorCode) {
            if ($errorCode === 28) {
                $this->log('curl_errno:28,message:Response timeout. Your request has probably be received by JPush Server');
            } else if ($errorCode === 56) {
                $this->log('curl_errno:56,message:Response timeout, maybe cause by old CURL version. Your request has probably be received by JPush Server');
            } else if ($times >= $this->retryTimes) {
                $this->log('Connect timeout. Please retry later.-- curl_errno:'.$errorCode.',message:'.curl_error($ch));
            } else {
                $this->log("Send " . $method . " " . $url . " fail, curl_code:" . $errorCode . ", body:" . $body . ", times:" . $times);
                $this->_request($url, $method, $body, ++$times);
            }
        } else {
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $header_text = substr($output, 0, $header_size);
            $body = substr($output, $header_size);
            /*$headers = array();
            foreach (explode("\r\n", $header_text) as $i => $line) {
                if (!empty($line)) {
                    if ($i === 0) {
                        $headers['http_code'] = $line;
                    } else if (strpos($line, ": ")) {
                        list ($key, $value) = explode(': ', $line);
                        $headers[$key] = $value;
                    }
                }
            }
            $response['headers'] = $headers;*/
            /*$response['body'] = $body;
            $response['http_code'] = $httpCode;*/

            $tmp = json_decode($body);
            $tmp->http_code = $httpCode;
            $response = json_encode($tmp);
        }
        curl_close($ch);
        return $response;
    }
    public function log($content) {
        if (!is_null($this->logFile)) {
            error_log($content . "\r\n", 3, $this->logFile);
        }
    }

    //获取获取消息的推送结果
    public function getReceived($msg_ids){
        $queryParams = '?msg_ids=';
        $ids = '';
        if (is_array($msg_ids) && !empty($msg_ids)) {
            $msgIdsStr = implode(',', $msg_ids);
            $queryParams .= $msgIdsStr;
            $ids = $msgIdsStr;
        } elseif (is_string($msg_ids)) {
            $queryParams .= $msg_ids;
            $ids = $msg_ids;
        }
        $queryParams = self::REPORT_URL.$queryParams;
        $response = $this->_request($queryParams, self::HTTP_GET);
        $this->log("查询消息{$ids}的推送结果" . $response);
        return json_decode( $response,true );
    }
}
