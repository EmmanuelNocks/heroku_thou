<?php
error_reporting(E_ERROR | E_PARSE);
ini_set('memory_limit', '-1');
set_time_limit(0);
require_once dirname(__FILE__).'/Thou.php';
require_once __DIR__ . '/vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
$app = new Silex\Application();
$app->register(new Predis\Silex\ClientServiceProvider(), [
    'predis.parameters' => getenv('REDIS_URL'),
]);

try{
    $T2 = date("Y-m-d h:i:s");
    $T1 = date('Y-m-d H:i:s',strtotime('-10 minutes',strtotime($T2)));
    $datetime1 =  explode(" ",$T1);
    $date1 = implode("",explode("-",$datetime1[0]));
    $time1 = implode("",explode(":",$datetime1[1]));
    $datetime1 =$datetime1[0]."T".$datetime1[1];

    $datetime2 =  explode(" ",$T2);
    $date2 = implode("",explode("-",$datetime2[0]));
    $time2 = implode("",explode(":",$datetime2[1]));
    $datetime2 =$datetime2[0]."T".$datetime2[1];
    $app['predis']->set('created_before',$$datetime2);
    echo $app['predis']->get('created_before');
    $rabbitmq = parse_url(getenv('CLOUDAMQP_URL'));
    $app->register(new Amqp\Silex\Provider\AmqpServiceProvider, [
        'amqp.connections' => [
            'default' => [
                'host'     => $rabbitmq['host'],
                'port'     => isset($rabbitmq['port']) ? $rabbitmq['port'] : 5672,
                'username' => $rabbitmq['user'],
                'password' => $rabbitmq['pass'],
                'vhost'    => substr($rabbitmq['path'], 1) ?: '/',
            ],
        ],
    ]);

        $connection = $app['amqp']['default'];
        $channel = $connection->channel();

        $channel->queue_declare('task_queue', false, true, false, false);
        $msg = new AMQPMessage($datetime1.";".$datetime2, array('delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT));
        $channel->basic_publish($msg, '', 'task_queue');
        echo " [x] Sent'\n";
        $channel->close();
        $connection->close();
    


}
catch(Exeption $e){

    print_r($e);
}
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
