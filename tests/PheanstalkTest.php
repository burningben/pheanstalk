<?php

require_once '../src/_autoload.php';

use Pheanstalk\Pheanstalk;

$servers = array(
    array(
        '0.0.0.0',
        '11300',
        ),
    array(
        '0.0.0.0',
        '11301',
        ),
    );

$ps = new Pheanstalk($servers);

//---------------------------------
// test listTubes()

// $res = $ps->listTubes();
// var_export($res);exit;

//---------------------------------
// test listTubesWathced()

// $ps->watch('d');
// $ps->watch('a');
// $res = $ps->listTubesWatched(true);
// var_export($res);exit;

//---------------------------------
// test stats()

$res = $ps->stats();
print_r($res);exit;

//---------------------------------
//test put and reserve distributely

$customal_tube = 'a';

$job_id = $ps
    ->useTube($customal_tube)
    ->put("a's job", 1024, 0, 60, $customal_tube);

$job = $ps->watch($customal_tube)->reserve($customal_tube);
echo $job->getData() . "\n";

$customal_tube = 'd';

$job_id = $ps
    ->useTube($customal_tube)
    ->put("d's job", 1024, 0, 60, $customal_tube);

$job = $ps->watch($customal_tube)->reserve($customal_tube);
echo $job->getData() . "\n";

