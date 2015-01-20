<?php
include_once('header.php');
$demon_handle = Loader::pgetService("demon");
$demon_handle->run('runPushMessage');
?>