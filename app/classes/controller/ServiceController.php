<?php
class ServiceController extends BaseController
{
    protected $_header_params_list = array(
                                        'sdk_version' =>'HTTP_SDK_VERSION',
                                        'regist_id'   =>'HTTP_REGIST_ID',
                                        'device_token'=>'HTTP_DEVICE_TOKEN',
                                        'device_info' =>'HTTP_DEVICE_INFO',
                                        'appid'       =>'HTTP_APPID');
    protected $_header_params_pregx = array(
                                        'sdk_version'=>"/^\d\.\d\.\d(\.\d)?$/",
                                        'regist_id'=>"/^[\da-z]{5,100}$/i",
                                        'device_token'=>"/^([\da-z-]{10,100})?$/i",
                                        'device_info'=>"/^{[^{}]{10,500}}$/",
                                        'appid'=>"/^([\da-z]{2,20})?$/i",
                                        );
    
    public function clientActionSendAction(){
        $params_list = array('pid','action');
        $pregx_list = array(
                        "pid"=>"/^[\da-z-_]{5,50}$/i",
                        'action'=>"/^[\d]$/",
                        );
        foreach($params_list as $val){
            $params[$val] = $this->getRequest($val);
        }
        
        try{
            $header_params = $this->getHeaderParams();
            $this->checkHeaderParams();
            $this->checkParmas($params,$pregx_list);
            $account_condition = array(
                                    'pid'=>$params['pid'],
                                    'action'=>$params['action'],
                                    );
            $back_data['ret'] = true;
            
            if($params['action']=='0'){
                $back_data['detail'] = array('taglist'=>null);
                //$back_data['detail'] = array('taglist'=>array());
            }elseif($params['action']=='1'){
                $back_data['detail'] = array('taglist'=>null);
                //$back_data['detail'] = array('taglist'=>array(31,58,61));
            }else{
                throw new SystemError("参数错误", SystemError::PARAMS_WORONG);
            }

            $log = 'header_params:'.json_encode($header_params).'| post_params:'.json_encode($params).'| return:'.json_encode($back_data);
            BaseLog::setClientActionLog($log);
            $this->jsonOutput($back_data);
        }catch(SystemError $e){
            $this->displayJsonError($e);
        }
    }

    public function getClientAction(){
         $date=$this->getRequest('date');
         $appid=$this->getRequest('appid');
         $filename=BaseLog::getLogPath().'/Client-Action'.$date.'.log';
         if(!file_exists($filename)){
            echo '文件不存在';
            exit;
         }
         $handle = @fopen($filename, "r");
        if ($handle) {
            if($appid){
                 while (!feof($handle)) {
                    $buffer = fgets($handle, 4096);
                    preg_match_all('/\"appid\":\"(.*?)\"/', $buffer, $matches);
                    if($matches[1][0]==$appid){
                     echo $buffer.'<Br/>';
                   }
                 }
            }else{
                 while (!feof($handle)) {
                    $buffer = fgets($handle, 4096);
                    echo $buffer.'<Br/>';
                 }
            }
           
            fclose($handle);
        }
        exit;
    }

}

