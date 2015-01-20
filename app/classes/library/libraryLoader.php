<?php
/**
 * library自动加载器
 * 
 * @package framework
 * @author chen.yi
 * @copyright 2013.08.28
 * @version $1.0$
 */
class libraryLoader
{
    public static function Register(){
        if (function_exists('__autoload')){
            spl_autoload_register('__autoload');
        }
        return spl_autoload_register(array('libraryLoader', 'Load'));
    }



    public static function Load($pClassName){
        if ((class_exists($pClassName))){
            return FALSE;
        }
        
        $library_file_path = LIBDIR.DIRECTORY_SEPARATOR.$pClassName.'.php';
        if ((file_exists($library_file_path) === FALSE) || (is_readable($library_file_path) === FALSE)){
            return FALSE;
        }
        
        require($library_file_path);
    }
}
libraryLoader::Register();