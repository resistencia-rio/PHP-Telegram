i<?php
$run = exec("ps -ef | grep 'process.php' | grep -v grep");
if($run) return;
$command = 'php '.__DIR__.'/process.php &';
pclose(popen('php '.__DIR__.'/process.php &', 'r'));
