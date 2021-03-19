<?php
/**
 * topic交换机 topic通过判断绑定键与路由键是否匹配
 */
require_once __DIR__ . '/vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Exchange\AMQPExchangeType;

/**
 * $argv  传递给脚本的参数数组
 * $argc  传递给脚本的参数数目
 */
$routeKey = $argv[1] ?? 'info';

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
$channel->exchange_declare('mq_sms_send_ex6', AMQPExchangeType::TOPIC, false, false, false);

//生成消息
//消息持久化 设置delivery_mode为2
$msg = new AMQPMessage($msg_str);

//推送消息到某个交换机
//参数：消息，交换机，路由键名
$channel->basic_publish($msg, 'mq_sms_send_ex6', $routeKey);
echo ' [X] Sent: ' . $msg_str . "\n";

$channel->close();
$connection->close();


















