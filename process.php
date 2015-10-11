<?php
require 'config.php';
do {
    if(!shell_exec('pgrep telegram-cli')) {
        $cmd = $config['telegram.cli']['path'].'/bin/telegram-cli --rsa-key '.$config['telegram.cli']['rsa-key'].' --tcp-port '.$config['telegram.cli']['tcp-port'].' &';
        pclose(popen($cmd, 'r'));
    }
    // micro second is one millionth of a second
    usleep(1000000);
} while (1);