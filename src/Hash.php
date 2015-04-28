<?php

namespace Pheanstalk;

/**
 * hash interface
 *
 * @author ben <burningben@qq.com>
 */
interface Hash
{

    public function addServer($hostname ,$port, $connectTimeout = null);

    public function findServer($key);

}
