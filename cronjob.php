<?php
$run = exec("ps -ef | grep 'process.php' | grep -v grep");
if($run) return;
$path = realpath(dirname(__FILE__));
pclose(popen("php $path/process.php > $path/log &", 'r'));
