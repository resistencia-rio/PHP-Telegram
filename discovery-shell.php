#!/usr/bin/env php
<?php
/**
 * Copyright 2015 Eric Enold <zyberspace@zyberware.org>
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
use PhpTelegram\Client;
require('vendor/autoload.php');
require 'config.php';
try {
    if($config['telegram.cli']['tcp-port']) {
        $remoteSocket = 'tcp://localhost:'.$config['telegram.cli']['tcp-port'];
    } elseif($config['telegram.cli']['udp-socket']) {
        $remoteSocket = 'unix://'.$config['telegram.cli']['udp-socket'];
    } else {
        throw new Exception('Inform the type of connection (tcp || socket)');
    }
    $telegram = new Client($remoteSocket);
} catch(Exception $e) {
    echo $e->getMessage();
    die();
}

$discoverShell = new \Zyberspace\DiscoveryShell($telegram, 'telegram', __DIR__ . '/.developer-shell-history');
$discoverShell->run();
