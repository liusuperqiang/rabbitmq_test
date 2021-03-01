<?php
require_once __DIR__ . '/vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exchange\AMQPExchangeType;

//消费者与mq之间建立连接
$connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');

//在已连接的基础上建立消费者与mq之间的通道
$channel = $connection->channel();

//初始化交换机
//参数：交换机名，路由类型，是否检测同名队列，是否开启队列持久化，通道关闭后是否删除队列
$channel->exchange_declare('mq_sms_send_ex', AMQPExchangeType::DIRECT, false, true, false);

//声明初始化一条队列
//参数：队列名，是否检测同名队列，是否开启队列持久化，是否能被其他队列访问，通道关闭后是否删除队列
$channel->queue_declare('mq_sms_send_q', false, false, false, false);

//将队列与某个交换机进行绑定，并使用路由关键字
//参数：队列名，交换机名，路由键名
$channel->queue_bind('mq_sms_send_q', 'mq_sms_send_ex', 'sms_send');

echo ' [*] Waiting for messages', "\n";

$callback = function ($msg) {
    //打印消息
    echo '[X] Received ' . $msg->body, "\n";

    //消息确认
    $msg->ack();

    //判断获取到quit后退出
    if (trim($msg->body) == 'quit') {
//        $msg->getChannel()->basic_cancel($msg->getConsumerTag());
        $msg->getChannel()->basic_cancel($msg->getConsumerTag());
    }
};

//参数：队列名，消费者标识，不接受此使用者发布的消息，消费者是否使用自动确认模式，请求独占使用者访问，不等待，回调
$channel->basic_consume('mq_sms_send_q', 'consumer1', false, false, false, false, $callback);

//退出方法
function shutdown ($channel, $connection)
{
    $channel->close();
    $connection->close();
}

//注册退出函数
register_shutdown_function('shutdown', $channel, $connection);

//监听通道消息
while (count($channel->callbacks)) {
    $channel->wait();
}
