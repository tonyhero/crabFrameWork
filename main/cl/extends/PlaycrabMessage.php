<?php
class PlaycrabMessage{
    
    const SEND_SUCCESS = 0;//发送成功
    const SIGN_FAIL = 1;//签名失败
    const SEND_FAIL = 2;//短信发送失败
    const IP_NO_PERMISSION = 3;//此IP为鉴权失败
    const APPID_NO_PERMISSION = 5;//应用被禁用
    const APPID_NOT_EXIST = 6;//此应用不存在
    const MODEL_ID_UNUSEFUL = 7;//该模板为无效模板
    
    private $_send_result_map = array(
                                self::SEND_SUCCESS=>'发送成功',
                                self::SIGN_FAIL=>'签名失败',
                                self::SEND_FAIL=>'短信发送失败',
                                self::IP_NO_PERMISSION=>'此IP为鉴权失败',
                                self::APPID_NO_PERMISSION=>'应用被禁用',
                                self::APPID_NOT_EXIST=>'此应用不存在',
                                self::MODEL_ID_UNUSEFUL=>'该模板为无效模板',
                                );
    
    
	public static function send($to,$app_id,$model_id,$key,$message,$throwException = false){
        $url = "http://message.playcrab.com/api/do-send";
        $params = array(
			'to'=>$to,
			'type'=>'message',
			'app_id'=>$app_id,
			'model_id' => $model_id,
			'datas'=>$message
		);
        $params['sign'] = self::getSign($params,$key);
        $post = http_build_query($params);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_POST,1);
        curl_setopt($ch, CURLOPT_POSTFIELDS,$post);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        $return_back = curl_exec($ch);
        $curl_errno = curl_errno($ch);
        curl_close($ch);
        $json_send_contents = json_encode($params);
        if($curl_errno > 0){
            BaseLog::setMobileMessageErrorLog("url:".$url."| send contents: $json_send_contents| curl-error: $curl_errno");
            if($throwException) throw new SystemError("短信发送失败",SystemError::MOBILE_MESSAGE_NETWORK_CURL_FAIL);
            return false;
        }
        
        $result = @json_decode($return_back, true);
        $logMsg = 'send contents:'.$json_send_contents.' | result:'.$return_back;
        if(!isset($result['result']) || ($result['result'] != self::SEND_SUCCESS)){
            BaseLog::setMobileMessageErrorLog($logMsg);
            if($throwException){
                throw new SystemError("短信发送失败",SystemError::MOBILE_MESSAGE_SEND_FAIL);
            }
            return false;
        }
        BaseLog::setMobileMessageSuccessLog($logMsg);
        return true;
     }



    public static function getSign($params,$key){
        ksort($params);
        $sign_str = http_build_query($params).$key;
        //BaseLog::setMobileMessageErrorLog($sign_str);
        $sign = md5($sign_str);
        return $sign;
    }
}
?>
