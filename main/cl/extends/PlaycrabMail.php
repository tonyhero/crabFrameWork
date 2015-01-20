<?php
class PlaycrabMail{
    //发送邮件
    public static function sendMail($to, $subject, $content, $from = 'zongle@zonglemail.com', $sender = '纵乐',$throwException = false){
        $to = is_array($to)? implode(';',$to) : $to;
        $url    = 'https://sendcloud.sohu.com/webapi/mail.send.json';
        $params = array(
            'to'       => $to,
            'from'     => $from,
            'html'     => $content,
            //'api_key'  => 'X5hxr71m',
            'api_key'  => '8xs4IorOpVrxPyba',
            'subject'  => $subject,
            'api_user' => 'postmaster@zongle.sendcloud.org',
            'fromname' => $sender,
        );  
        $ch     = curl_init();
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_URL,$url);
        //不同于登录
        curl_setopt($ch, CURLOPT_POSTFIELDS,$params);
        $return_back = curl_exec($ch);
        $curl_errno = curl_errno($ch);
        curl_close($ch);
        $json_send_contents = json_encode($params);
        if($curl_errno > 0){
            BaseLog::setMailErrorLog("url:".$url."| send contents: $json_send_contents| error: $curl_errno");
            if($throwException) throw new SystemError("mail curl fail",SystemError::MAIL_NETWORK_CURL_FAIL);
            return false;
        }
        $result = @json_decode($return_back, true);
        
        $logMsg = 'send contents:'.$json_send_contents.' | result:'.$return_back;
        if(!isset($result['message']) || ($result['message'] !== 'success')){
            BaseLog::setMailErrorLog($logMsg);
            if($throwException){
                throw new SystemError("mail send fail",SystemError::MAIL_SEND_FAIL);
            }
            return false;
        }
        BaseLog::setMailSuccessLog($logMsg);
        return true;
    }

}
?>
