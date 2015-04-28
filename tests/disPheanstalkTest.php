<?php

require_once '../src/_autoload.php';

use Pheanstalk\Pheanstalk;

// 配置
$servers = array(
    array(
        '192.168.102.119', // 2
        '11300',
        ),
    array(
        '192.168.102.156', // 3
        '11300',
        ),
    array(
        '192.168.102.125',
        '11300',
        ),
    array(
        '192.168.102.157', // 5
        '11300',
        ),
    array(
        '192.168.102.158',
        '11300',
        ),
    array(
        '192.168.102.159', // 7
        '11300',
        ),
    array(
        '192.168.102.128',
        '11300',
        ),
    );

$options = getopt("c:t:");

$tube_ct = $options['c']; // count

$client_tag = $optoins['t']; // tag

// 主程序
$t1 = microtime(true);

$ps = new Pheanstalk($servers);

//---------------------------------
//test put and reserve distributely

for ($i=0; $i < $tube_ct; $i++) { 
    $tube_name = $client_tag . "tube" . $i;
    $ps->useTube($tube_name)->put($tube_name . str_pad('a',10240), 1024, 0, 60, $tube_name); // 1MB
}

for ($i=0; $i < $tube_ct; $i++) { 
    $tube_name = $client_tag . "tube" . $i;
    $jobs[] = $ps->watch($tube_name)->reserve($tube_name)->getId();
}

// print_r($jobs);
$t2 = microtime(true);
echo '耗时' . round($t2 - $t1, 3) . '秒 job_ct:' . count($jobs);
exit;
