<?php

/**
 * 纵乐登陆系统服务
 * 
 * @author  yeyongfa<zhangjianwai813@gmail.com>
 * @copyright 2014
 */
class account_svc
{
    /**
     * 修改用户安全信息
     *
     * @param array params 要修改的参数(uid、type:修改的用户信息,email|mobile、value:对应type类型数据的值) 
     * @param string action 操作接口
     *
     * @return array
     */
    public function changeSafeInfo(array $params, $action = 'api/change-safe-info')
    {
        $sign_data = array(
            'uid'       => $params['uid'],
            'type'      => $params['type'],
            'token'     => time(),
        );
        $params['sign']     = Tools::sign($sign_data);
        $params['token']    = $sign_data['token'];
        $params['operator'] = Loader::pgetService('user')->getUsername();
        $params['orderid']  = isset($params['orderid']) ? $params['orderid'] : 'null';
        $params['username'] = isset($params['username'])  ? $params['username']  : 'null';

        $url = Loader::getSelfConfigParams("ACCOUNT_URL").'/'.trim($action, '\/'); 
        $res_data = json_decode(HttpRequest::_curl_post($url, $params), true);

        switch($res_data['result'])
        {
            case 98: 
                throw new SystemError("系统错误", SystemError::ACCOUNT_ERROR);
                break;
                
            case 94: 
                throw new SystemError($res_data['description'], SystemError::PARAM_ERROR);
                break;

            case 14: 
                throw new SystemError($res_data['description'], SystemError::ACCOUNT_NO_USER);
                break;

            case 31: 
                throw new SystemError($res_data['description'], SystemError::MOBILE_EXISTS);
                break;

            case 91: 
                throw new SystemError($res_data['description'], SystemError::ACCOUNT_SIGN_ERROR);
                break;

            case 90: 
                throw new SystemError($res_data['description'], SystemError::ACCOUNT_MATCH_ERROR);
                break;

            default: 
                $des_map = array(
                    'email'  => '邮箱', 
                    'mobile' => '手机' 
                );
                $action = ($params['type'] == 'email') ? 'cs_change_email' : 'cs_change_mobile';
                $description = "客服系统将".$params['username']."(uid:".$params['uid']."),".$des_map[$params['type']]."修改为:".$params['value'];

                $params['orderid']  = isset($params['orderid']) ? $params['orderid'] : null;
                $oplog_model = Loader::pgetModel("oplog");
                $oplog_model->log($params['operator'], $action, $description, $params['orderid'], $params['hash']);
                return $res_data;
                break;
        }
    } 

}
