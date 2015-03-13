<?php
namespace Pheanstalk\Command;

use Pheanstalk\Exception;
use Pheanstalk\Response;

/**
 * The 'clear-tube' command.
 *
 * @author ben <burninben@qq.com>
 * @package Pheanstalk
 * @licence http://www.opensource.org/licenses/mit-license.php
 */
class ClearTubeCommand
    extends AbstractCommand
    implements \Pheanstalk\ResponseParser
{
    private $_tube;

    /**
     * @param string the name of the tube
     */
    public function __construct($tube)
    {
        $this->_tube = $tube;
    }
    /* (non-phpdoc)
     * @see Pheanstalk_Command::getCommandLine()
     */
    public function getCommandLine()
    {
        return 'clear-tube '.$this->_tube;
    }

    /* (non-phpdoc)
     * @see Pheanstalk_ResponseParser::parseRespose()
     */
    public function parseResponse($responseLine, $responseData)
    {
        if ($responseLine == Response::RESPONSE_NOT_FOUND) {
            throw new Exception\ServerException(sprintf(
                'Cannot clear tube %s: %s',
                $this->_tube,
                $responseLine
            ));
        }
        var_dump($responseLine);
        return $this->_createResponse($responseLine);
    }
}
