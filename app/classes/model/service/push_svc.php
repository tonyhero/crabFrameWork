<?php

/**
 * push_svc
 * 用于提供手机消息推送服务
 * @author    yichen
 * @copyright 2014
 */
class push_svc
{
    /**
     * 推送消息
     *
     * @param array $sendinfo
     * $sendinfo['gamename'] 游戏名称
     * $sendinfo['cname'] 渠道名称
     * $sendinfo['platform'] 平台
     * $sendinfo['pushinfo_id'] 推送信息的id
     * $sendinfo['sendcontent']
     * $sendinfo['upload_filepath']
     * $sendinfo['offline_message_keeptime']
     * $sendinfo['ios_sound']
     * $sendinfo['android_notify_title']
     * $sendinfo['tagid']
     * @return void
     */
    public function pushMessage($sendinfo)
    {
        $pushData['platform']                =$sendinfo['platform'];
        if($sendinfo['tagid']){
            $pushData['audience']['tag']     =$sendinfo['tagid'];
        }else if(''!=$sendinfo['upload_filepath']){
            $path=Loader::getSelfConfigParams("UPLOAD_PATH");
            $fp=file($path.$sendinfo['upload_filepath']);
            $pushData['audience']['alias']   =$fp;
        }else{
            $pushData['audience']            ='all';
        }
        $pushData['notification'][$pushData['platform']]['alert']=$sendinfo['sendcontent'];
        if($pushData['platform']=='ios'){
            $pushData['notification']['ios']['sound']=$sendinfo['ios_sound'];
            $pushData['notification']['ios']['badge']='1';
        }else if($pushData['platform']=='android'){
            $pushData['notification']['android']['title']=$sendinfo['android_notify_title'];
        }
        
        //$pushData['message']['msg_content']  =$sendinfo['sendcontent'];
        $pushData['options']['sendno']       =$sendinfo['pushinfo_id'];
        $pushData['options']['time_to_live'] =$sendinfo['offline_message_keeptime'];
        $pushData['options']['apns_production'] = (Loader::getSelfConfigParams("PUSH-ENVIRONMENT")=='dev')? false : true;

        $base='Basic '.base64_encode($sendinfo['push_key'].':'.$sendinfo['secret']);
        $url= 'https://api.jpush.cn/v3/push';
        $res=HttpRequest::postJsonWithoutParams_with_exception($url,json_encode($pushData),$base);
        $msg=json_decode($res,true);

        $model = Loader::pgetModel('pushinfo');
        if(isset($msg['error'])){
           $model->updateFailToSending($sendinfo['pushinfo_id']);
           $model->update(array('pushinfo_id'=>$sendinfo['pushinfo_id']),array('msg_id'=>$msg['msg_id']));
           BaseLog::setDemonErrorLog($sendinfo['pushinfo_id'].'|'.json_encode($pushData).'|'.$res);
        }else{
           $model->updateSuccessToSending($sendinfo['pushinfo_id']);
           $model=Loader::pgetModel('effecthistory');
           $model->update(array('pushinfo_id'=>$sendinfo['pushinfo_id']),array('msg_id'=>$msg['msg_id']));
        }
        
    }
    
    public function getMessage($sendinfo){
        $msg_id=implode(',', $sendinfo['msg_id']);
        $base='Basic '.base64_encode($sendinfo['push_key'].':'.$sendinfo['secret']);
        $url= 'https://report.jpush.cn/v2/received?msg_ids='.$msg_id;
        $res=HttpRequest::getWithoutParams_with_exception($url,$base);
        $msg=json_decode($res,true);
        if(isset($msg['error'])){
           BaseLog::setDemonErrorLog($res);
           return;
        }else{
           foreach ($msg as $v) {
               $ios_apns_sent=$v['ios_apns_sent']?$v['ios_apns_sent']:0;
               $android_received=$v['android_received']?$v['android_received']:0;
               $model = Loader::pgetModel('effecthistory');
              $model->update(array('msg_id'=>$v['msg_id']),array('ios_effect'=>'--'.$ios_apns_sent.'-','android_effect'=>'--'.$android_received.'-'));
           }
        }
        
    }
}
