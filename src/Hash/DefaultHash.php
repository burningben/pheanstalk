<?php
namespace Pheanstalk\Hash;

use Pheanstalk\Exception;
use Pheanstalk\Hash;
use Pheanstalk\Connection;

/**
 * default hash using 
 *
 * @author ben <burningben@qq.com>
 */
class DefaultHash implements Hash
{

    /**
     * 保存已增加的服务器的连接
     * array (
     *      new Connection(),
     *      new Connection(),
     * )
     */
    public $servers = array();

    /**
     * init
     * 
     * @param array $servers e.g array (array('0.0.0.0', 11300, 1),array('0.0.0.0', 11300, 1))
     */
    public function __construct($servers) {
        $this->_checkServersParam($servers);
        foreach ($servers as $key => $item) {
            $this->servers[] = new Connection($item[0], $item[1], $item[2]);
        }
    }

    public function addServer($hostname ,$port, $connectTimeout = null) 
    {
        $this->servers[] = new Connection($host, $port, $connectTimeout);
    }

    public function findServer($key) {
        $server_num = count($this->servers);
        if ($server_num < 1) {
            throw Exception\ClientException('no server exist');
        }
        if ($server_num == 1) {
            return $this->servers[0];
        }
        $hash = crc32($key);
        return $this->servers[$hash % $server_num];
    }

    private function _checkServersParam(&$servers) {
        if (!is_array($servers)) {
            throw Exception\ClientException("servers param error");
        }
        foreach ($servers as $key => $item) {
            if (!isset($item[0]) || !isset($item[1])) {
                throw Exception\ClientException("servers param error");
            }
            $servers[$key][2] = isset($item[2]) ? $item[2] : '';
        }
    }

}


