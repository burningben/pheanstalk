<?php

require_once '../src/_autoload.php';

use Pheanstalk\Pheanstalk;

// 配置
$servers = array(
    array(
        '192.168.102.128',
        '11300',
        ),
    );

$options = getopt("c:");
$tube_ct = $options['c']; // count

// 主程序
$t1 = microtime(true);

$ps = new Pheanstalk($servers);

//---------------------------------
//test put and reserve distributely

for ($i=0; $i < $tube_ct; $i++) { 
    $tube_name = "tube" . $i;
    $ps->useTube($tube_name)->put($tube_name . str_pad('a',10240), 1024, 0, 60, $tube_name); // 1MB
}

for ($i=0; $i < $tube_ct; $i++) { 
    $tube_name = "tube" . $i;
    $jobs[] = $ps->watch($tube_name)->reserve($tube_name)->getId();
}


// print_r($jobs);
$t2 = microtime(true);
echo '耗时' . round($t2 - $t1, 3) . '秒 job_ct:' . count($jobs);
exit;
    