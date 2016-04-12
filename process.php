<?php
require 'config.php';
do {
    if(!shell_exec('pgrep telegram-cli')) {
        $cmd = $config['telegram.cli']['path'].
            (isset($config['telegram.cli']['rsa-key'])
                ?' --rsa-key '.$config['telegram.cli']['rsa-key']
                :'').
            (isset($config['telegram.cli']['tcp-port'])
                ?' --tcp-port '.$config['telegram.cli']['tcp-port']
                :'').
            (isset($config['telegram.cli']['udp-socket'])
                ?' --udp-socket '.$config['telegram.cli']['udp-socket']
                :'').
            //prints answers and values in json format
            ' --json'.
            //daemon mode
            //To stop the daemon use killall telegram-cli or kill -TERM [telegram-pid]
            ' --daemonize'.
            //send dialog_list query and wait for answer before reading input
            ' --wait-dialog-list'.
            ' &';
        pclose(popen($cmd, 'r'));
    }
    // micro second is one millionth of a second
    usleep(1000000);
} while (1);