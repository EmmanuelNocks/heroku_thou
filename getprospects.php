<?php
error_reporting(E_ERROR | E_PARSE);
ini_set('memory_limit', '-1');
set_time_limit(0);
require_once dirname(__FILE__).'/Thou.php';
require_once __DIR__ . '/vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
$connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
$channel = $connection->channel();
$channel->queue_declare('task_queue', false, true, false, false);
echo " [*] Waiting for messages. To exit press CTRL+C\n";
$callback = function ($msg) {
    echo ' [x] Received ', $msg->body, "\n";
    sleep(substr_count($msg->body, '.'));
    echo " [x] Done\n";
    $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
};
$channel->basic_qos(null, 1, null);
$channel->basic_consume('task_queue', '', false, false, false, false, $callback);
while (count($channel->callbacks)) {
    $channel->wait();
}
$channel->close();
$connection->close();


// $T2 = date("Y-m-d h:i:s");
// $T1 = date('Y-m-d H:i:s',strtotime('-10 minutes',strtotime($T2)));
// $datetime1 =  explode(" ",$T1);
// $date1 = implode("",explode("-",$datetime1[0]));
// $time1 = implode("",explode(":",$datetime1[1]));
// $datetime1 =$datetime1[0]."T".$datetime1[1];

// $datetime2 =  explode(" ",$T2);
// $date2 = implode("",explode("-",$datetime2[0]));
// $time2 = implode("",explode(":",$datetime2[1]));
// $datetime2 =$datetime2[0]."T".$datetime2[1];

// $instance = new Thou();
// $data = $instance->getProspects($datetime1,$datetime2);


// foreach ($data as $key => $value) {
//     $instance-> lookUpProspect($value->id, $value->email);
// }
