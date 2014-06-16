<?php
/**
 * 
 */

require_once 'app/controllers/authenticated_controller.php';

class DocumentController extends AuthenticatedController
{
    protected $download_handle = null;
    protected $download_remove = null;
    
    public function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);

        // Lock context to user id
        $this->context_id = $GLOBALS['user']->id;

        if (Request::isXhr()) {
            $this->set_layout(null);
        } else {
            $this->set_layout($GLOBALS['template_factory']->open('layouts/base'));
        }
        $this->userConfig = DocUsergroupConfig::getUserConfig($GLOBALS['user']->id);
        if($this->userConfig['area_close'] == 1){
            $this->redirect('document/closed/index');
        }
    }

    protected function setDialogLayout($icon = false)
    {
        $layout = $this->get_template_factory()->open('document/dialog-layout.php');
        $layout->icon = $icon;

        if (!Request::isXhr()) {
            $layout->set_layout($GLOBALS['template_factory']->open('layouts/base'));
        }

        $this->set_layout($layout);
    }

    protected function initiateDownload($inline, $filename, $mime_type, $size, $handle)
    {
        $response = $this->response;

        if ($_SERVER['HTTPS'] === 'on') {
            $response->add_header('Pragma', 'public');
            $response->add_header('Cache-Control', 'private');
        } else {
            $response->add_header('Pragma', 'no-cache');
            $response->add_header('Cache-Control', 'no-store, no-cache, must-revalidate');
        }

        $dispositon = sprintf('%s;filename="%s"',
                              $inline ? 'inline' : 'attachment',
                              urlencode($filename));
        $response->add_header('Content-Disposition', $dispositon);
        $response->add_header('Content-Description', 'File Transfer');
        $response->add_header('Content-Transfer-Encoding' , 'binary');
        $response->add_header('Content-Type', $mime_type);
        $response->add_header('Content-Length', $size);

        $this->render_nothing();

        $this->download_handle = $handle;
    }

    public function after_filter($action, $args)
    {
        parent::after_filter($action, $args);

        if ($this->download_handle !== null && is_resource($this->download_handle)) {
            fpassthru($this->download_handle);
            fclose($this->download_handle);
        }
        if ($this->download_remove !== null && ($this->download_remove) && file_exists($this->download_remove)) {
            unlink($this->download_remove);
        }
    }
}