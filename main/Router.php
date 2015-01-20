<?php
class Router
{
    
    //默认地址,必须设置,可自行更改
    private static $default = array(
                        "controller"=>"index",
                        "action"=>"indexAction",);
    
    //必须设置,找不到Controller或者Action的报错页面
    private static $error = array(
                        "controller"=>"error",
                        "action"=>"indexAction",);
                        
    public static function run($uri = null){
        self::checkController();
        $request_uri = is_null($uri)? strtolower($_SERVER['REQUEST_URI']) : $uri;
        //$pregx = preg_match("/\/([a-z]+)(\/([a-z]+)[\?]?)?/",$request_uri,$match);
        $pregx = preg_match("/\/(([a-z]+)\/?$|([a-z]+)\/([a-z_]+)[\?]?)/",$request_uri,$match);
        $count = count($match);
        switch($count){
            case 3:
                $controller_name = $match[2];
                $action_name = self::$default['action'];
                break;
            case 5:
                $controller_name = $match[3];
                $action_name = $match[4].'Action';
                break;
            default:
                $controller_name = self::$default['controller'];
                $action_name = self::$default['action'];
        }
        //echo($controller_name."|".$action_name);die();
        self::loadControllerAction($controller_name,$action_name);
    }
    
    //加载控制器和方法
    public static function loadControllerAction($controller_name,$action_name){
        $controller_class_name = self::getControllerClassName($controller_name);
        $controller_file_path = self::getControllerPath($controller_name);
        if(!file_exists($controller_file_path)){
            $controller_name = self::$error['controller'];
            $action_name = self::$error['action'];
            $controller_class_name = self::getControllerClassName($controller_name);
            $controller_file_path = self::getControllerPath($controller_name);
        }
        require_once($controller_file_path);
        if(!method_exists($controller_class_name,$action_name)){
            $controller_name = self::$error['controller'];
            $action_name = self::$error['action'];
            $controller_class_name = self::getControllerClassName($controller_name);
            $controller_file_path = self::getControllerPath($controller_name);
            require_once($controller_file_path);
        }
        $class_object = new $controller_class_name();
        $class_object->$action_name();
    }
    
    private static function checkController(){
        $check_controller_list = array(
                                    self::$default,
                                    self::$error,
                                    );
        foreach($check_controller_list as $ca){
            $controller_name = $ca['controller'];
            $action_name = $ca['action'];
            $controller_class_name = self::getControllerClassName($controller_name);
            $controller_file_path = self::getControllerPath($controller_name);
            $header = '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
            if(!file_exists($controller_file_path)){
                echo($header);
                echo('Controller :'.$controller_class_name.' Not Exist');exit();
            }
            require_once($controller_file_path);
            if(!method_exists($controller_class_name,$action_name)){
                echo($header);
                echo('Action :'.$controller_class_name.'->'.$action_name.' Not Exist');exit();
            }
        }
    }
    
    
    private static function getControllerClassName($controller_name){
        return ucfirst($controller_name)."Controller";
    }
    
    private static function getControllerPath($controller_name){
        $controller_class_name = ucfirst($controller_name)."Controller";
        return WEBROOT."/classes/controller/".self::getControllerClassName($controller_name).".php";
    }
}
?>