<?php
/**
 * wiki.php - wiki controller (currently only a helper)
 *
 * @author  Jan-Hendrik Willms <tleilax+studip@gmail.com>
 * @license GPL2 or any later version
 * @since   3.3
 */
require_once 'app/controllers/authenticated_controller.php';
require_once 'lib/wiki.inc.php';

class WikiController extends AuthenticatedController
{
    public function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);
        
        $this->keyword  = Request::get('keyword');
        $this->range_id = $GLOBALS['SessSemName'][1];
    }

    public function store_action($version)
    {
        $body = Request::get('body');
        if (Request::isXhr()) {
            $body = studip_utf8decode($body);
        }
        
        submitWikiPage($this->keyword, $version, $body, $GLOBALS['user']->id, $this->range_id);

        $latest_version = getLatestVersion($this->keyword, $this->range_id);

        if (Request::isXhr()) {
            $this->render_json(array(
                'version'  => $latest_version['version'],
                'body'     => $latest_version['body'],
                'messages' => implode(PageLayout::getMessages()) ?: false,
                'zusatz'   => getZusatz($latest_version),
            ));
        } else {
            // Yeah, wait for the whole trailification of the wiki...
        }
    }

    public function version_check_action($version)
    {
        $latest_version = getLatestVersion($this->keyword, $this->range_id);

        if (!$latest_version && $version > 1) {
            $this->response->add_header('X-Studip-Error', _('Diese Wiki-Seite existiert nicht mehr!'));
            $this->render_json(false);
        } elseif ($latest_version && $version != $latest_version['version']) {
            $error  = _('Die von Ihnen bearbeitete Seite ist nicht mehr aktuell.') . ' ';
            $error .= _('Falls Sie dennoch speichern, überschreiben Sie die getätigte Änderung und es wird unter Umständen zu Datenverlusten kommen.');
            $this->response->add_header('X-Studip-Error', $error);

            $this->response->add_header('X-Studip-Confirm', _('Möchten Sie Ihre Version dennoch speichern?'));

            $this->render_json(null);
        } else {
            $this->render_json(true);
        }
    }
}
