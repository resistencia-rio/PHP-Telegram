<?php
do {
    if(!shell_exec('pgrep telegram-cli')) {
        $cmd = __DIR__."/../tg/bin/telegram-cli -k ~/.ssh/telegram.pub -P 2015 &";
        pclose(popen($cmd, 'r'));
    }
    // micro second is one millionth of a second
    usleep(1000000);
} while (1);