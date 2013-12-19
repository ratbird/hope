<?php
/**
 * @author  <mlunzena@uos.de>
 * @license GPL 2 or later
 * @todo
 */

namespace API;
use \Exception;

class RouterHalt extends Exception
{
    public function __construct($response)
    {
        $this->response = $response;
    }
}
