<?php
/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */
require_once 'app/controllers/studip_controller.php';
require_once 'app/models/plugin_administration.php';

class PluginsController extends StudipController
{

    public function trigger_automaticupdate_action($class)
    {
        $plugin =  \PluginManager::getInstance()->getPluginInfo($class);
        $low_cost_secret = md5($GLOBALS['STUDIP_INSTALLATION_ID'].$plugin['id']);
        $output = array();

        if ($plugin['automatic_update_url'] && ($low_cost_secret === \Request::option("s"))) {
            if ($plugin['automatic_update_secret'] && !$this->verify_secret($plugin['automatic_update_secret'])) {
                throw new AccessDeniedException("Not allowed.");
            }

            $update_url = $plugin['automatic_update_url'];
            require_once 'app/models/plugin_administration.php';

            $plugin_admin = new \PluginAdministration();
            try {
                $plugin_admin->installPluginFromURL($update_url);
            } catch (Exception $e) {
                $output['exception'] = $e->getMessage();
            }
        }
        if (!count($output)) {
            $output['message'] = "ok";
        }
        $this->render_json($output);
    }

    protected function verify_secret($secret) {
        if (!isset($_SERVER['HTTP_X_HUB_SIGNATURE'])) {
            return true;
        }
        $signatureHeader = $_SERVER['HTTP_X_HUB_SIGNATURE'];
        $payload = file_get_contents('php://input');
        list($algorithm, $hash) = explode('=', $signatureHeader, 2);

        $calculatedHash = hash_hmac($algorithm, $payload, $secret);
        return $calculatedHash === $hash;
    }

}