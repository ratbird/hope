<?php
namespace API;

/**
 * @author  <mlunzena@uos.de>
 * @license GPL 2 or later
 * @since   Stud.IP 3.0
 */
class RouterHalt extends \Exception
{
    public function __construct($response)
    {
        $this->response = $response;
    }
}
