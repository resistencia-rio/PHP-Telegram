<?php
do {
    if(!shell_exec('pgrep telegram-cli')) {
        $cmd = __DIR__."/../tg/bin/telegram-cli -c ".__DIR__."/../tg-conf/config -k ".__DIR__."/../tg-conf/telegram.pub -P 2015 > ".__DIR__."/../tg-config/log &";
        pclose(popen($cmd, 'r'));
    }
    // micro second is one millionth of a second
    usleep(50000);
} while (1);