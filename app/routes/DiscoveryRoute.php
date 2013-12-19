<?php
namespace API;

/**
 * @author  Jan-Hendrik Willms <tleilax+studip@gmail.com>
 * @license GPL 2 or later
 */
class DiscoveryRoute extends RouteMap
{
    /**
     * Schnittstellenbeschreibung
     *
     * @get /discovery
     */
    public function getDiscovery()
    {
        $routes = $this->router->getRoutes(true);
        foreach ($routes as $uri_template => $methods) {
            foreach ($methods as $method => $route) {
                $routes[$uri_template][$method] = $route['description'];
            }
        }
        return $routes;
    }
}
