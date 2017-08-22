<?php 

/**
 * connection test to Proto server
 */

require_once __DIR__ . '/vendor/autoload.php'; // Autoload files using Composer autoload

use Proto\ProtoTest;
use Proto\Socket;


$t = new ProtoTest();
$Socket = new Socket();
$Socket->socket_connect("127.0.0.1","9111");
$t->SetSocket($Socket);
$t->RunSendTest();
$t->RunReceiveTest();