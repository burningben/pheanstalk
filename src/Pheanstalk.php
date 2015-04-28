<?php

namespace Pheanstalk;
require_once '_autoload.php';

/**
 * Pheanstalk is a PHP client for the beanstalkd workqueue.
 * The Pheanstalk class is a simple facade for the various underlying components.
 *
 * @see http://github.com/kr/beanstalkd
 * @see http://xph.us/software/beanstalkd/
 *
 * @author Paul Annesley
 * @author ben <burningben@qq.com>
 * @package Pheanstalk
 * @licence http://www.opensource.org/licenses/mit-license.php
 */
class Pheanstalk implements PheanstalkInterface
{
    const VERSION = '3.0.2';

    private $hash;

    private $_connection;
    private $_using = PheanstalkInterface::DEFAULT_TUBE;
    private $_watching = array(PheanstalkInterface::DEFAULT_TUBE => true);

    public function __construct($servers) {
        $this->hash = new Hash\DefaultHash($servers);
    }

    /**
     * {@inheritDoc}
     */
    public function setConnection(Connection $connection)
    {
        $this->_connection = $connection;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getConnection()
    {
        return $this->_connection;
    }

    // ----------------------------------------

    /**
     * {@inheritDoc}
     */
    public function bury($job, $priority = PheanstalkInterface::DEFAULT_PRIORITY, $tube = 'default')
    {
        $this->_connection = $this->hash->findServer($tube);

        $this->_dispatch(new Command\BuryCommand($job, $priority));
    }

    /**
     * {@inheritDoc}
     */
    public function delete($job, $tube = 'dafault')
    {
        $this->_connection = $this->hash->findServer($tube);
        
        $this->_dispatch(new Command\DeleteCommand($job));

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function ignore($tube)
    {
        $this->_connection = $this->hash->findServer($tube);
        
        if (isset($this->_watching[$tube])) {
            $this->_dispatch(new Command\IgnoreCommand($tube));
            unset($this->_watching[$tube]);
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function kick($max, $tube = 'dafault')
    {
        $this->_connection = $this->hash->findServer($tube);
        
        $response = $this->_dispatch(new Command\KickCommand($max));

        return $response['kicked'];
    }

    /**
     * {@inheritDoc}
     */
    public function kickJob($job, $tube = 'dafault')
    {
        $this->_connection = $this->hash->findServer($tube);
        
        $this->_dispatch(new Command\KickJobCommand($job));

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function listTubes()
    {
        $res = array();

        foreach ($this->hash->servers as $key => $item) {
            $this->_connection = $item;
            $tmp = (array) $this->_dispatch(
                new Command\ListTubesCommand()
            );
            $res = array_merge($res, $tmp);
        }

        return array_unique($res);
    }

    /**
     * {@inheritDoc}
     */
    public function listTubesWatched($askServer = false)
    {
        if ($askServer) {
            $res = array();

            foreach ($this->hash->servers as $key => $item) {
                $this->_connection = $item;
                $tmp = (array) $this->_dispatch(
                    new Command\ListTubesWatchedCommand()
                );
                $res = array_merge($res, $tmp);
            }
            $res = array_merge($res, $tmp);

            $this->_watching = array_fill_keys($res, true);
        }

        return array_keys($this->_watching);
    }

    /**
     * {@inheritDoc}
     */
    public function listTubeUsed()
    {
        return $this->_using;
    }

    /**
     * {@inheritDoc}
     */
    public function pauseTube($tube, $delay)
    {
        $this->_connection = $this->hash->findServer($tube);
        
        $this->_dispatch(new Command\PauseTubeCommand($tube, $delay));

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function resumeTube($tube)
    {
        $this->_connection = $this->hash->findServer($tube);
        
        // Pause a tube with zero delay will resume the tube
        $this->pauseTube($tube, 0);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function clearTube($tube)
    {
        $this->_connection = $this->hash->findServer($tube);
        
        $this->_dispatch(new Command\ClearTubeCommand($tube));
    }

    /**
     * {@inheritDoc}
     */
    public function peek($jobId, $tube = 'dafault')
    {
        $this->_connection = $this->hash->findServer($tube);
        
        $response = $this->_dispatch(
            new Command\PeekCommand($jobId)
        );

        return new Job($response['id'], $response['jobdata']);
    }

    /**
     * {@inheritDoc}
     */
    public function peekReady($tube = null)
    {
        $this->_connection = $this->hash->findServer($tube);
        
        if ($tube !== null) {
            $this->useTube($tube);
        }

        $response = $this->_dispatch(
            new Command\PeekCommand(Command\PeekCommand::TYPE_READY)
        );

        return new Job($response['id'], $response['jobdata']);
    }

    /**
     * {@inheritDoc}
     */
    public function peekDelayed($tube = null)
    {
        $this->_connection = $this->hash->findServer($tube);
        
        if ($tube !== null) {
            $this->useTube($tube);
        }

        $response = $this->_dispatch(
            new Command\PeekCommand(Command\PeekCommand::TYPE_DELAYED)
        );

        return new Job($response['id'], $response['jobdata']);
    }

    /**
     * {@inheritDoc}
     */
    public function peekBuried($tube = null)
    {
        $this->_connection = $this->hash->findServer($tube);
        
        if ($tube !== null) {
            $this->useTube($tube);
        }

        $response = $this->_dispatch(
            new Command\PeekCommand(Command\PeekCommand::TYPE_BURIED)
        );

        return new Job($response['id'], $response['jobdata']);
    }

    /**
     * {@inheritDoc}
     */
    public function put(
        $data,
        $priority = PheanstalkInterface::DEFAULT_PRIORITY,
        $delay = PheanstalkInterface::DEFAULT_DELAY,
        $ttr = PheanstalkInterface::DEFAULT_TTR,
        $tube = 'dafault'
    )
    {
        $this->_connection = $this->hash->findServer($tube);

        $response = $this->_dispatch(
            new Command\PutCommand($data, $priority, $delay, $ttr)
        );

        return $response['id'];
    }

    /**
     * {@inheritDoc}
     */
    public function putInTube(
        $tube,
        $data,
        $priority = PheanstalkInterface::DEFAULT_PRIORITY,
        $delay = PheanstalkInterface::DEFAULT_DELAY,
        $ttr = PheanstalkInterface::DEFAULT_TTR
    )
    {
        $this->useTube($tube);

        return $this->put($data, $priority, $delay, $ttr, $tube);
    }

    /**
     * {@inheritDoc}
     */
    public function release(
        $job,
        $priority = PheanstalkInterface::DEFAULT_PRIORITY,
        $delay = PheanstalkInterface::DEFAULT_DELAY,
        $tube = 'default'
    )
    {
        $this->_connection = $this->hash->findServer($tube);
        
        $this->_dispatch(
            new Command\ReleaseCommand($job, $priority, $delay)
        );

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function reserve($tube = 'default', $timeout = null)
    {
        $this->_connection = $this->hash->findServer($tube);

        $response = $this->_dispatch(
            new Command\ReserveCommand($timeout)
        );
        $falseResponses = array(
            Response::RESPONSE_DEADLINE_SOON,
            Response::RESPONSE_TIMED_OUT,
        );

        if (in_array($response->getResponseName(), $falseResponses)) {
            return false;
        } else {
            return new Job($response['id'], $response['jobdata']);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function reserveFromTube($tube, $timeout = null)
    {
        $this->_connection = $this->hash->findServer($tube);
        
        $this->watchOnly($tube);

        return $this->reserve($tube, $timeout);
    }

    /**
     * {@inheritDoc}
     */
    public function statsJob($job, $tube = 'default')
    {
        $this->_connection = $this->hash->findServer($tube);
        
        return $this->_dispatch(new Command\StatsJobCommand($job));
    }

    /**
     * {@inheritDoc}
     */
    public function statsTube($tube)
    {
        $this->_connection = $this->hash->findServer($tube);
        
        return $this->_dispatch(new Command\StatsTubeCommand($tube));
    }

    /**
     * {@inheritDoc}
     */
    public function stats()
    {
        $res = array();

        foreach ($this->hash->servers as $key => $item) {
            $this->_connection = $item;
            $res[] = $this->_dispatch(new Command\StatsCommand());
        }

        return $res;
    }

    /**
     * {@inheritDoc}
     */
    public function touch($job, $tube = 'default')
    {
        $this->_connection = $this->hash->findServer($tube);
        
        $this->_dispatch(new Command\TouchCommand($job));

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function useTube($tube)
    {
        $this->_connection = $this->hash->findServer($tube);

        if ($this->_using != $tube) {
            $this->_dispatch(new Command\UseCommand($tube));
            $this->_using = $tube;
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function watch($tube)
    {
        $this->_connection = $this->hash->findServer($tube);

        if (!isset($this->_watching[$tube])) {
            $this->_dispatch(new Command\WatchCommand($tube));
            $this->_watching[$tube] = true;
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function watchOnly($tube)
    {
        $this->watch($tube);

        $ignoreTubes = array_diff_key($this->_watching, array($tube => true));
        foreach ($ignoreTubes as $ignoreTube => $true) {
            $this->ignore($ignoreTube);
        }

        return $this;
    }

    // ----------------------------------------

    /**
     * Dispatches the specified command to the connection object.
     *
     * If a SocketException occurs, the connection is reset, and the command is
     * re-attempted once.
     *
     * @param  Command  $command
     * @return Response
     */
    private function _dispatch($command)
    {
        try {
            $response = $this->_connection->dispatchCommand($command);
        } catch (Exception\SocketException $e) {
            $this->_reconnect();
            $response = $this->_connection->dispatchCommand($command);
        }

        return $response;
    }

    /**
     * Creates a new connection object, based on the existing connection object,
     * and re-establishes the used tube and watchlist.
     */
    private function _reconnect()
    {
        $new_connection = new Connection(
            $this->_connection->getHost(),
            $this->_connection->getPort(),
            $this->_connection->getConnectTimeout()
        );

        $this->setConnection($new_connection);

        if ($this->_using != PheanstalkInterface::DEFAULT_TUBE) {
            $tube = $this->_using;
            $this->_using = null;
            $this->useTube($tube);
        }

        foreach ($this->_watching as $tube => $true) {
            if ($tube != PheanstalkInterface::DEFAULT_TUBE) {
                unset($this->_watching[$tube]);
                $this->watch($tube);
            }
        }

        if (!isset($this->_watching[PheanstalkInterface::DEFAULT_TUBE])) {
            $this->ignore(PheanstalkInterface::DEFAULT_TUBE);
        }
    }
}
