<?php
class ErrorController extends BaseController
{
    
    //找不到此页面
    function indexAction(){
        Tools::view('common/404', array());
    }
}

?>
