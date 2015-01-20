<?php
class HttpRequest
{
    /**
    * @author yichen
    * @param  sting   $url    请求的地址
    * @param  sting   $method 请求方式
    * @param  array   $data   发送的请求数据(method为post时生效)
    * @param  array   $header 生成请求的头部信息
    * @param  boolean $getHeader 是否获取返回数据的header头信息(默认不获取)
    * @param  boolean $throw_exception 失败情况下是否抛出异常
    * @param  int     $timeout 设置请求超时时间(单位秒)
    * @return string
    */
    public static function send($url,$method,$data = array(),$header = array(),$getHeader = false,$throw_exception = true,$timeout = 30){
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch,CURLOPT_NOBODY,0);
        if('post'===$method){
            curl_setopt($ch,CURLOPT_POST,1);
            if(is_array($data)) curl_setopt($ch,CURLOPT_POSTFIELDS,$data);
        }else{
            curl_setopt($ch,CURLOPT_POST,0);
        }
        if(false===$getHeader){
            curl_setopt($ch,CURLOPT_HEADER,0);
        }else{
            curl_setopt($ch,CURLOPT_HEADER,1);
        }
        if($header) curl_setopt($ch,CURLOPT_HTTPHEADER,$header);       

        curl_setopt($ch,CURLOPT_TIMEOUT,$timeout);
        $response = curl_exec($ch);
        $curl_errno = curl_errno($ch);
        curl_close($ch);
        if($curl_errno>0){
            BaseLog::setCurlErrorLog("url:".$url."|error: $curl_errno");
            if(true === $throw_exception) throw new SystemError("curl post fail",SystemError::CURL_FAIL);
            return false;
            //print curl_error($ch);
        }
        return $response;
    }
    
    public static function postXmlWithoutParams($url,$xmldata){
        $header[] = "Content-type: text/xml";//定义content-type为xml
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch,CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch,CURLOPT_POST, 1);
        curl_setopt($ch,CURLOPT_POSTFIELDS,$xmldata);
        $response = curl_exec($ch);
        $curl_errno = curl_errno($ch);
        curl_close($ch);
        if($curl_errno>0){
            BaseLog::setCurlErrorLog("url:".$url."|error: $curl_errno");
            throw new SystemError("curl post fail",SystemError::CURL_FAIL);
            //var_dump($curl_errno);
        }
        return $response;
    }
    
    public static function postJsonWithoutParams_with_exception($url,$jsondata,$auth = null){
        $header[] = "Content-Type: application/json";//定义content-type为json
        if(!is_null($auth)) $header[] = "Authorization: $auth";
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch,CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch,CURLOPT_POST, 1);
        curl_setopt($ch,CURLOPT_POSTFIELDS,$jsondata);
        $response = curl_exec($ch);
        $curl_errno = curl_errno($ch);
        curl_close($ch);
        if($curl_errno>0){
            BaseLog::setCurlErrorLog("url:".$url."|error: $curl_errno");
            throw new SystemError("curl post fail",SystemError::CURL_FAIL);
            //print curl_error($ch);
        }
        return $response;
    }
       public static function getWithoutParams_with_exception($url,$auth = null){
        //$header[] = "Content-Type: application/json";//定义content-type为json
        if(!is_null($auth)) $header[] = "Authorization: $auth";
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch,CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch,CURLOPT_POST, 0);
        $response = curl_exec($ch);
        $curl_errno = curl_errno($ch);
        curl_close($ch);
        if($curl_errno>0){
            BaseLog::setCurlErrorLog("url:".$url."|error: $curl_errno");
            throw new SystemError("curl post fail",SystemError::CURL_FAIL);
            //print curl_error($ch);
        }
        return $response;
    }
    
    public static function _curl_post($url, $vars,$timeout = 5){
        $field = http_build_query($vars);
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch,CURLOPT_POST,1);
        curl_setopt($ch,CURLOPT_TIMEOUT,$timeout);
        //curl_setopt($ch,CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
        curl_setopt($ch,CURLOPT_HEADER,0);
        curl_setopt($ch,CURLOPT_POSTFIELDS, $field);
        $data = curl_exec($ch);
        $curl_errno = curl_errno($ch);
        curl_close($ch);
        if($curl_errno > 0){
            BaseLog::setCurlErrorLog("url:".$url."|error: $curl_errno");
            //throw new SystemError("curl post fail",SystemError::CURL_FAIL);
            return false;
        }else{
            return $data;
        }
    }
    
    public static function _curl_post_with_exception($url, $vars,$timeout = 5){
        $field = http_build_query($vars);
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch,CURLOPT_POST,1);
        curl_setopt($ch,CURLOPT_TIMEOUT,$timeout);
        //curl_setopt($ch,CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
        curl_setopt($ch,CURLOPT_HEADER,0);
        curl_setopt($ch,CURLOPT_POSTFIELDS, $field);
        $data = curl_exec($ch);
        $curl_errno = curl_errno($ch);
        curl_close($ch);
        if($curl_errno > 0){
            BaseLog::setCurlErrorLog("url:".$url."|error: $curl_errno");
            throw new SystemError("curl post fail",SystemError::CURL_FAIL);
        }
    }

    public static function _curl_get($url,$timeout = 30){
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch,CURLOPT_POST,0);
        curl_setopt($ch,CURLOPT_TIMEOUT,$timeout);
        //curl_setopt($ch,CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
        curl_setopt($ch,CURLOPT_HEADER,0);
        $data = curl_exec($ch);
        $curl_errno = curl_errno($ch);
        curl_close($ch);
        if($curl_errno > 0){
            BaseLog::setCurlErrorLog("url:".$url."|error: $curl_errno");
            //throw new SystemError("curl get fail",SystemError::CURL_FAIL);
            return false;
        }else{
            return $data;
        }
    }
    
    
    public static function _curl_get_with_exception($url,$timeout = 30){
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch,CURLOPT_POST,0);
        curl_setopt($ch,CURLOPT_TIMEOUT,$timeout);
        //curl_setopt($ch,CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
        curl_setopt($ch,CURLOPT_HEADER,0);
        $data = curl_exec($ch);
        $curl_errno = curl_errno($ch);
        curl_close($ch);
        if($curl_errno > 0){
            BaseLog::setCurlErrorLog("url:".$url."|error: $curl_errno");
            throw new SystemError("curl get fail",SystemError::CURL_FAIL);
            return false;
        }else{
            return $data;
        }
    }

}

?>
