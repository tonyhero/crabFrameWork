<?php
set_time_limit(0);
ini_set('include_path','/home/playcrab/work/crabframework');
ini_set('memory_limit','4096M');
define("WEBROOT",dirname(dirname(__FILE__)));
define("LIBDIR",WEBROOT.'/classes/library');
ini_set('date.timezone','PRC');
ini_set("display_errors","On");
ini_set("error_log",'/data/work/web/log/php.log');
error_reporting(E_ALL);
//error_reporting(0);

//加载框架头部
include_once('framework-header.php');

/*************** 加载应用自有类 ****************/
require_once(LIBDIR."/libraryLoader.php");
require_once(LIBDIR."/base/__autoLoader.php");

//应用相关的静态数据文件
require_once(WEBROOT."/config/app-static-config/RedisConfig.php");
?>