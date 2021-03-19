<?php
require_once __DIR__ . '/vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exchange\AMQPExchangeType;

/**
 * 订阅消息
 */

$connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
$channel = $connection->channel();

//声明初始化交换机
//参数：交换机名，路由类型，是否检测同名队列，是否开启队列持久化，通道关闭后是否删除队列
$channel->exchange_declare('mq_sms_send_ex4', AMQPExchangeType::FANOUT, false, false, false);

//声明初始化一条队列
//参数：队列名，是否检测同名队列，是否开启队列持久化，是否能被其他队列访问，通道关闭后是否删除队列
list($queue_name, ,) = $channel->queue_declare('', false, false, true, false);

//将队列与某个交换机进行绑定，并使用路由关键字
//参数：队列名，交换机名，路由键名
$channel->queue_bind($queue_name, 'mq_sms_send_ex4');

echo ' [*] Waiting for messages. To exit press CTRL+C', "\n";

$callback = function($msg) {
    echo " [x] Received ", $msg->body, "\n";

    //判断获取到quit后
    if (trim($msg->body) == 'quit') {

        $msg->getChannel()->basic_cancel($msg->getConsumerTag());
    }

};

$channel->basic_qos(null, 1, null);

//
//参数：队列名，消费者标识符，不接收此使用者发布的消息，使用者是否使用自动确认模式，请求独占使用者访问，不等待，消息回调函数
$channel->basic_consume($queue_name, 'consumer1', false, true, false, false, $callback);


function shutdown($channel, $connection)
{
    $channel->close();
    $connection->close();
}

register_shutdown_function('shutdown', $channel, $connection);

while(count($channel->callbacks)) {
    $channel->wait();
}
