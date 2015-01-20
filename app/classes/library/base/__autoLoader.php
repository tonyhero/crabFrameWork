<?php
/**
 * AppBaseLoader自动加载器
 * 
 * @package framework
 * @author chen.yi
 * @copyright 2013.08.28
 * @version $1.0$
 */
class AppBaseLoader
{
    public static function Register(){
        if (function_exists('__autoload')){
            spl_autoload_register('__autoload');
        }
        return spl_autoload_register(array('AppBaseLoader', 'Load'));
    }



    public static function Load($pClassName){
        if ((class_exists($pClassName))){
            return FALSE;
        }
        
        $appBase_file_path = LIBDIR.DIRECTORY_SEPARATOR.'base'.DIRECTORY_SEPARATOR.$pClassName.'.php';
        if ((file_exists($appBase_file_path) === FALSE) || (is_readable($appBase_file_path) === FALSE)){
            return FALSE;
        }
        
        require($appBase_file_path);
    }
}
AppBaseLoader::Register();