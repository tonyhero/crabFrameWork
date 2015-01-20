<?php
if (!function_exists('getallheaders')) {
    function getallheaders()
    {
        $headers = '';
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }

        return $headers;
    }
}
class BaseController
{
    protected $_header_params = null;
    protected $_header_params_list = array();
    protected $_header_params_pregx = array();
    
    function __construct(){
        
    }
    
    //只允许内网访问
    protected function allowAccessUri($ip = null)
    {
        $ip = is_null($ip)? '106.37.232.114' : $ip;
        if ($_SERVER['REMOTE_ADDR'] != $ip && $_SERVER['REMOTE_ADDR'] != '127.0.0.1') {
            if(!isset($_SERVER['HTTP_X_FORWARDED_FOR']) || $_SERVER['HTTP_X_FORWARDED_FOR'] != $ip){
                exit('not allowed');
            }
        }
    }

    protected function getRequest($param_name, $default = '')
    {
        if (isset($_REQUEST[$param_name])) {
            $param = $_REQUEST[$param_name];
            if (is_array($param)) {
                return $param;
            } else {
                $param = trim($param);

                return ($param == "") ? $default : $param;
            }
        }

        return $default;
    }

    protected function getPost($param_name, $default = '')
    {
        if (isset($_POST[$param_name])) {
            $param = $_POST[$param_name];
            if (is_array($param)) {
                return $param;
            } else {
                $param = trim($param);
                return ($param == "") ? $default : $param;
            }
        }
        return $default;
    }

    protected function checkParmas($params, $pregx_list)
    {
        foreach ($params as $key => $val) {
            if (isset($pregx_list[$key]) && (!preg_match($pregx_list[$key], $val))) {
                //error_log('key:'.$key."| val:".$val);
                throw new SystemError("参数错误", SystemError::PARAMS_WORONG);
            }
        }
    }

    //检查header头部信息
    protected function checkHeaderParams(){
        $this->checkParmas($this->getHeaderParams(),$this->_header_params_pregx);
    }
    
    protected function getHeaderParams(){
        if(is_null($this->_header_params)){
            $this->_header_params = array();
            foreach($this->_header_params_list as $keyname=>$headname){
                $this->_header_params[$keyname] = isset($_SERVER[$headname])? $_SERVER[$headname] : '';
            }
        }
        return $this->_header_params;
    }

    protected function pageAlertError($e)
    {
        header("Content-type: text/html;charset=utf-8");
        echo("<script language='javascript'>alert('" . $e->getMessage() . "');history.back(-1);</script>");
        exit();
    }

    protected function pageAlertSuccessRedirect($success_message, $redirect_url = null)
    {
        header("Content-type: text/html;charset=utf-8");
        $redirect_url = is_null($redirect_url) ? '/' : $redirect_url;
        echo("<script language='javascript'>alert('$success_message');location.href='$redirect_url';</script>");
        exit();
    }

    protected function displayJsonError($e,$error_flag = 'ret')
    {
        $error_back[$error_flag] = false;
        $error_back['errNo']  = $e->getCode();
        $error_back['errMsg'] = $e->getMessage();
        $this->jsonOutput($error_back);
    }

    protected function jsonOutput($array)
    {
        header('Content-type:application/json');
//        header("Content-type: text/html");
        echo(json_encode($array));
        exit;
    }

    protected function textplainOutput($text)
    {
        header('Content-type:text/plain');
        echo($text);
        exit;
    }

    protected function cactiOutput($output)
    {
        header('Content-type:text/plain');
        foreach ($output as $key => $val) {
            echo($key . '=' . $val . "\n");
        }
        exit;
    }

    //页面跳转
    protected function pageRedirect($uri)
    {
        header("location:$uri");
        exit();
    }


    protected function getControllerActionName()
    {
        $uri = $_SERVER['REQUEST_URI'];
        preg_match("/^\/([a-z]+)\/?([a-z]+)?/i", $uri, $match);
        $default_ControllerName = 'index';
        $default_ActionName     = 'index';
        $ca['controller_name']  = isset($match[1]) ? strtolower($match[1]) : $default_ControllerName;
        $ca['action_name']      = isset($match[2]) ? strtolower($match[2]) : $default_ActionName;

        return $ca;
    }


    protected function appNameMap($appid)
    {
        $game_name = array(
            '100' => '乱世曲',
            '101' => '大掌门',
            '102' => '大富翁',
            '103' => '巨人',
            '104' => '龙珠',
            '105' => '恶魔地下城',
            '106' => '众神之王',
        );

        $channel_name = array(
            '01' => '测试(体验服)',
            '02' => 'Appstore版',
            '03' => 'IOS越狱版',
            '04' => '安卓版',
            '99' => '特殊版',
        );

        $app_name_map = str_split($appid, 3);
        $app_name     = $game_name[$app_name_map[0]] . '--' . $channel_name[$app_name_map[1]];

        return $app_name;
    }


}

?>
