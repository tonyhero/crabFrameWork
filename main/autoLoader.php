<?php
require_once('CPathConfig.php');
/**
 * 框架类自动加载器
 * 
 * @package framework
 * @author chen.yi
 * @copyright 2013.08.28
 * @version $1.0$
 */
class autoLoader
{
    public static function Register(){
        if (function_exists('__autoload')){
            spl_autoload_register('__autoload');
        }
        return spl_autoload_register(array('autoLoader', 'Load'));
    }



    public static function Load($pClassName){
        if ((class_exists($pClassName))){
            return FALSE;
        }
        
        global $Class_Path_Config;
        if(!isset($Class_Path_Config[$pClassName])){
            return FALSE;
        }
        $class_file_path = FRAME_WORK_ROOT.'/'.$Class_Path_Config[$pClassName];
        
        if ((file_exists($class_file_path) === FALSE) || (is_readable($class_file_path) === FALSE)){
            return FALSE;
        }
        
        require($Class_Path_Config[$pClassName]);
    }
}
autoLoader::Register();