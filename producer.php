<?php
require_once __DIR__ . '/vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Exchange\AMQPExchangeType;

//获取终端提示用户输入数据
//php cli中，三个系统常量：STDIN STDOUT STDERR 代表文件句柄
//STDIN 标准输入    STDOUT 标准输出    STDERR 标准错误
fwrite(STDOUT, 'Please enter a message:' . PHP_EOL);
$msg_str = fgets(STDIN);

//生产者与mq之间建立连接
$connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');

//在已连接的基础上建立生产者与mq之间的通道
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

//生成消息
$msg = new AMQPMessage($msg_str);

//推送消息到某个交换机
//参数：消息，交换机，路由键名
$channel->basic_publish($msg, 'mq_sms_send_ex', 'sms_send');
echo ' [X] Sent: ' . $msg_str . "\n";

$channel->close();
$connection->close();
