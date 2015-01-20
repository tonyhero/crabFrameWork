<?php
class BaseView
{
    private $_params = array();
    private $_viewpath = null;
    
    function __construct($html){
        $this->_viewpath = self::getView($html);
    }
    
    public static function getView($html){
        $html_name = $html.'.html';
        if(!preg_match("/^[\/]/",$html_name)){
            $html_name = "/".$html_name;
        }
        $view_dir = WEBROOT.'/view';
        $view_path = $view_dir.$html_name;
        //echo($view_path);die();
        return $view_path;
    }
    
    public function loadParams($page_params,$params){
        $this->_params[$page_params] = $params;
    }
    
    public function disPlay(){
        foreach($this->_params as $key=>$val){
            $$key = $val;
        }
        include($this->_viewpath);
    }
 }
?>