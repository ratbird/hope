<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO

class WebserviceClient
{

    function WebserviceClient()
    {
        trigger_error("this class can't be instantiated");
    }

    function instance($webservice_url, $classname)
    {
        static $instances = array();

        if (! $instances[$classname.$webservice_url])
        {
            $instances[$classname.$webservice_url] = new $classname($webservice_url);
        }

        return $instances[$classname.$webservice_url];
    }

    function &call($method_name, &$args)
    {
        trigger_error("WebserviceCaller::WebserviceCaller::  call not defined");
    }
}

