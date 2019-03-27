<?php
error_reporting(E_ERROR | E_PARSE);
ini_set('memory_limit', '-1');
set_time_limit(0);
require_once dirname(__FILE__).'/Thou.php';
require_once __DIR__ . '/vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
$app = new Silex\Application();
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
// $connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
// $channel = $connection->channel();
$channel->queue_declare('post_queue', false, true, false, false);
echo " [*] Waiting for messages. To exit press CTRL+C\n";
$callback = function ($msg) {
    echo " received\n";
   // print($msg->body);
//    $postdata =  json_decode($msg->body,false);
//     print($postdata);
// $instance = new Thou();
// $instance->post();
    $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
};
$channel->basic_qos(null, 1, null);
$channel->basic_consume('post_queue', '', false, false, false, false, $callback);
while (count($channel->callbacks)) {
    $channel->wait();
}
$channel->close();
$connection->close();
