<?php
error_reporting(E_ERROR | E_PARSE);
ini_set('memory_limit', '-1');
set_time_limit(0);
date_default_timezone_set("America/Chicago");
require_once dirname(__FILE__).'/Thou.php';
require_once __DIR__ . '/vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
$app = new Silex\Application();
$app->register(new Predis\Silex\ClientServiceProvider(), [
    'predis.parameters' => getenv('REDIS_URL'),
]);

try{
    
    $T2 = date("Y-m-d h:i:s");
    
    $datetime2 =  explode(" ",$T2);
    $date2 = implode("",explode("-",$datetime2[0]));
    $time2 = implode("",explode(":",$datetime2[1]));
    $datetime2 =$date2."T".$time2;

    // first time execution

    if($app['predis']->get('created_before')==null){
        $T1 = date('Y-m-d H:i:s',strtotime('-2 minutes',strtotime($T2)));
        $datetime1 =  explode(" ",$T1);
        $date1 = implode("",explode("-",$datetime1[0]));
        $time1 = implode("",explode(":",$datetime1[1]));
        $datetime1 =$date1."T".$time1;
        $app['predis']->set('created_before',$datetime1);

    }
    print_r($app['predis']->get('created_before2'));
    
    $datetime1 = $app['predis']->get('created_before');
    $app['predis']->set('created_before',$datetime2);
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
    print_r($datetime1."==".$datetime2);
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
