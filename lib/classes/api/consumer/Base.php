<?php
namespace API\Consumer;

/**
 * @author  Jan-Hendrik Willms <tleilax+studip@gmail.com>
 * @license GPL 2 or later
 * @todo    documentation
 */
abstract class Base
{
    abstract public function detect();
    abstract public function authenticate();
}
