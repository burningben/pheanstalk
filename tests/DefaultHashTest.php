<?php
use Pheanstalk\Hash\DefaultHash;

require_once '../src/_autoload.php';

$servers = array(array('0.0.0.0', 11300, 1),array('0.0.0.0', 11301, 1));
$hash = new DefaultHash($servers);
// print_r($hash->servers);exit;
echo crc32('a') . "\n";
print_r($hash->findServer('a'));
echo crc32('a') . "\n";
print_r($hash->findServer('a'));
echo crc32('b') . "\n";
print_r($hash->findServer('b'));
echo crc32('c') . "\n";
print_r($hash->findServer('c'));
echo crc32('d') . "\n";
print_r($hash->findServer('d'));
