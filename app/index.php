<?php
include_once('header.php');
//启动路由器
Router::run();

/************ 手动加载控制器和方法 *************
$controller = "test";
$action = "indexAction";
Router::loadControllerAction($controller,$action);
***********************************************/


/*************  框架使用参考说明 ***************
$instance = Loader::getLoader();//获取加载器句柄

//获取service对象句柄
//$instance->getService("test")->tt();

//加载,输出摸板
$tt = 'aaaaa';
$view = loader::loadView("test");
$view->loadParams("tt",$tt);
$view->disPlay();

//写入日志
BaseLog::setDbLog('hahahah');



//数据库连接,查询
$db_cluster = $instance->getDbCluster();
$db = $db_cluster->getDbMasterInstance();
$sql = "select * from pushdata";
var_dump($db->selectOne($sql));
$db_cluster->closeCluster();
******************************/

?>