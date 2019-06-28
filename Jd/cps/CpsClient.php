<?php

class CpsClient
{
    public $appkey;

    public $secretKey;

    public $gatewayUrl = 'https://router.jd.com/api';

    public $format = 'json';

    public $connectTimeout;

    public $readTimeout;

    /** 是否打开入参check**/
    public $checkRequest = true;

    protected $signMethod = 'md5';

    protected $apiVersion = '1.0';
    protected $sdkVersion = 'jd-sdk-php-20190522';

    public function getAppkey()
    {
        return $this->appkey;
    }

    public function __construct($appkey = '', $secretKey = '')
    {
        $this->appkey = $appkey;
        $this->secretKey = $secretKey;
    }

    protected function generateSign($params)
    {
        ksort($params);

        $stringToBeSigned = $this->secretKey;
        foreach ($params as $k => $v) {
            if (!is_array($v) && '@' != substr($v, 0, 1)) {
                $stringToBeSigned .= "$k$v";
            }
        }
        unset($k, $v);
        $stringToBeSigned .= $this->secretKey;

        return strtoupper(md5($stringToBeSigned));
    }

    public function curl($url, $postFields = null)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FAILONERROR, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if ($this->readTimeout) {
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->readTimeout);
        }
        if ($this->connectTimeout) {
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->connectTimeout);
        }
        curl_setopt($ch, CURLOPT_USERAGENT, 'jd-sdk-php');
        //https 请求
        if (strlen($url) > 5 && strtolower(substr($url, 0, 5)) == 'https') {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }

        if (is_array($postFields) && 0 < count($postFields)) {
            $postBodyString = '';
            $postMultipart = false;
            foreach ($postFields as $k => $v) {
                if ('@' != substr($v, 0, 1)) {//判断是不是文件上传
                    $postBodyString .= "$k=" . urlencode($v) . '&';
                } else {//文件上传用multipart/form-data，否则用www-form-urlencoded
                    $postMultipart = true;
                    if (class_exists('\CURLFile')) {
                        $postFields[$k] = new \CURLFile(substr($v, 1));
                    }
                }
            }
            unset($k, $v);
            curl_setopt($ch, CURLOPT_POST, true);
            if ($postMultipart) {
                if (class_exists('\CURLFile')) {
                    curl_setopt($ch, CURLOPT_SAFE_UPLOAD, true);
                } else {
                    if (defined('CURLOPT_SAFE_UPLOAD')) {
                        curl_setopt($ch, CURLOPT_SAFE_UPLOAD, false);
                    }
                }
                curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
            } else {
                $header = ['content-type: application/x-www-form-urlencoded; charset=UTF-8'];
                curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
                curl_setopt($ch, CURLOPT_POSTFIELDS, substr($postBodyString, 0, -1));
            }
        }
        $reponse = curl_exec($ch);

        if (curl_errno($ch)) {
            throw new Exception(curl_error($ch), 0);
        } else {
            $httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if (200 !== $httpStatusCode) {
                throw new Exception($reponse, $httpStatusCode);
            }
        }
        curl_close($ch);

        return $reponse;
    }

    public function curl_with_memory_file($url, $postFields = null, $fileFields = null)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FAILONERROR, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if ($this->readTimeout) {
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->readTimeout);
        }
        if ($this->connectTimeout) {
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->connectTimeout);
        }
        curl_setopt($ch, CURLOPT_USERAGENT, 'top-sdk-php');
        //https 请求
        if (strlen($url) > 5 && strtolower(substr($url, 0, 5)) == 'https') {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }
        //生成分隔符
        $delimiter = '-------------' . uniqid();
        //先将post的普通数据生成主体字符串
        $data = '';
        if ($postFields != null) {
            foreach ($postFields as $name => $content) {
                $data .= '--' . $delimiter . "\r\n";
                $data .= 'Content-Disposition: form-data; name="' . $name . '"';
                //multipart/form-data 不需要urlencode，参见 http:stackoverflow.com/questions/6603928/should-i-url-encode-post-data
                $data .= "\r\n\r\n" . $content . "\r\n";
            }
            unset($name, $content);
        }

        //将上传的文件生成主体字符串
        if ($fileFields != null) {
            foreach ($fileFields as $name => $file) {
                $data .= '--' . $delimiter . "\r\n";
                $data .= 'Content-Disposition: form-data; name="' . $name . '"; filename="' . $file['name'] . "\" \r\n";
                $data .= 'Content-Type: ' . $file['type'] . "\r\n\r\n"; //多了个文档类型

                $data .= $file['content'] . "\r\n";
            }
            unset($name, $file);
        }
        //主体结束的分隔符
        $data .= '--' . $delimiter . '--';

        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: multipart/form-data; boundary=' . $delimiter,
            'Content-Length: ' . strlen($data), ]
        );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        $reponse = curl_exec($ch);
        unset($data);

        if (curl_errno($ch)) {
            throw new Exception(curl_error($ch), 0);
        } else {
            $httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if (200 !== $httpStatusCode) {
                throw new Exception($reponse, $httpStatusCode);
            }
        }
        curl_close($ch);

        return $reponse;
    }

    protected function logCommunicationError($apiName, $requestUrl, $errorCode, $responseTxt)
    {
        $localIp = isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : 'CLI';
        $logger = new JdLogger();
        $logger->conf['log_file'] = rtrim(JD_SDK_WORK_DIR, '\\/') . '/' . 'logs/jd_comm_err_' . $this->appkey . '_' . date('Y-m-d') . '.log';
        $logger->conf['separator'] = '^_^';
        $logData = [
        date('Y-m-d H:i:s'),
        $apiName,
        $this->appkey,
        $localIp,
        PHP_OS,
        $this->sdkVersion,
        $requestUrl,
        $errorCode,
        str_replace("\n", '', $responseTxt),
        ];
        $logger->log($logData);
    }

    public function execute($request, $session = null, $bestUrl = null)
    {
        $result = new ResultSet();
        if ($this->checkRequest) {
            try {
                $request->check();
            } catch (Exception $e) {
                $result->code = $e->getCode();
                $result->msg = $e->getMessage();

                return $result;
            }
        }
        //组装系统参数
        $sysParams['app_key'] = $this->appkey;
        $sysParams['format'] = $this->format;
        $sysParams['method'] = $request->getApiMethodName();
        $sysParams['sign_method'] = $this->signMethod;
        $sysParams['timestamp'] = date('Y-m-d H:i:s');
        $sysParams['v'] = $this->apiVersion;




        if (null != $session) {
            $sysParams['session'] = $session;
        }
        $apiParams = [];
        //获取业务参数
        $apiParams = $request->getApiParas();

//		//系统参数放入GET请求串
        $requestUrl = $this->gatewayUrl . "?";
        $sysParams['param_json'] = json_encode($apiParams, JSON_FORCE_OBJECT);
        //签名
        $sysParams['sign'] = $this->generateSign($sysParams);

        foreach ($sysParams as $sysParamKey => $sysParamValue) {
            // if(strcmp($sysParamKey,"timestamp") != 0)
            $requestUrl .= "$sysParamKey=" . urlencode($sysParamValue) . "&";
        }

        $fileFields = [];
        foreach ($apiParams as $key => $value) {
            if (is_array($value) && array_key_exists('type', $value) && array_key_exists('content', $value)) {
                $value['name'] = $key;
                $fileFields[$key] = $value;
                unset($apiParams[$key]);
            }
        }

        //发起HTTP请求
        try {
            //            $apiParams = $sysParams;
            if (count($fileFields) > 0) {
                $resp = $this->curl_with_memory_file($requestUrl, $apiParams, $fileFields);
            } else {
                $resp = $this->curl($requestUrl);
            }
        } catch (Exception $e) {
            $this->logCommunicationError($sysParams['method'], $requestUrl, 'HTTP_ERROR_' . $e->getCode(), $e->getMessage());
            $result->code = $e->getCode();
            $result->msg = $e->getMessage();

            return $result;
        }

        unset($apiParams);
        unset($fileFields);
        //解析TOP返回结果
        $respWellFormed = false;
        if ('json' == $this->format) {
            $respObject = json_decode($resp);
            if (null !== $respObject) {
                $respWellFormed = true;
                foreach ($respObject as $propKey => $propValue) {
                    $respObject = $propValue;
                }
            }
        }
        //返回的HTTP文本不是标准JSON或者XML，记下错误日志
        if (false === $respWellFormed) {
            $this->logCommunicationError($sysParams['method'], $requestUrl, 'HTTP_RESPONSE_NOT_WELL_FORMED', $resp);
            $result->code = 0;
            $result->msg = 'HTTP_RESPONSE_NOT_WELL_FORMED';

            return $result;
        }
        $responseData = json_decode($respObject->result, true);
        //如果TOP返回了错误码，记录到业务错误日志中
        if (isset($responseData['code']) && $responseData['code'] !== 200) {
            $logger = new JdLogger();
            $logger->conf['log_file'] = rtrim(JD_SDK_WORK_DIR, '\\/') . '/' . 'logs/jd_biz_err_' . $this->appkey . '_' . date('Y-m-d') . '.log';
            $logger->log([
                date('Y-m-d H:i:s'),
                $resp,
            ]);
        }

        return $responseData;
    }


    private function getClusterTag()
    {
        return substr($this->sdkVersion, 0, 11) . '-cluster' . substr($this->sdkVersion, 11);
    }
}
