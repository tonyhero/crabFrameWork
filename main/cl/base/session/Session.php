<?php
class Session
{
    public static function start(){
        $save_handler = Loader::getSelfConfigParams("SESSION-SAVE-HANDLER");
        $save_handler = ($save_handler &&(''!=$save_handler))? $save_handler : 'files';
        ini_set('session.save_handler',$save_handler);
        
        $session_save_path = Loader::getSelfConfigParams("SESSION-SAVE-PATH");
        if($session_save_path) ini_set('session.save_path',$session_save_path);
        session_start();
    }
}
?>