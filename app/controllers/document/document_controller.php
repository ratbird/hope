<?php
/**
 *
 */

require_once 'app/controllers/authenticated_controller.php';

class DocumentController extends AuthenticatedController
{
    public function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);

        // Lock context to user id
        $this->context_id = $GLOBALS['user']->id;

        $this->limit = $GLOBALS['user']->cfg->PERSONAL_FILES_ENTRIES_PER_PAGE ?: Config::get()->ENTRIES_PER_PAGE;

        $this->userConfig = DocUsergroupConfig::getUserConfig($GLOBALS['user']->id);
        if ($this->userConfig['area_close'] == 1) {
            $this->redirect('document/closed/index');
        }

        if (Request::isPost()) {
            CSRFProtection::verifySecurityToken();
        }
        if (($ticket = Request::get('studip-ticket')) && !check_ticket($ticket)) {
            $message = _('Bei der Verarbeitung Ihrer Anfrage ist ein Fehler aufgetreten.') . "\n"
                     . _('Bitte versuchen Sie es erneut.');
            PageLayout::postMessage(MessageBox::error($message));
            $this->redirect('document/files/index');
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

    public function url_for_parent_directory($entry, $parent_id = null)
    {
        if (is_array($entry)) {
            $entry = reset($entry);
        }
        if (!is_object($entry)) {
            $entry = new DirectoryEntry($entry);
        }
        
        $parent_id   = $parent_id ?: FileHelper::getParentId($entry->id) ?: $this->context_id;
        $parent_page = $this->getPageForIndex($entry->indexInparent());
        return $this->url_for('document/files/index/' . $parent_id . '/' . $parent_page);
    }

    /**
     * Returns the page to display for a certain index of a file.
     *
     * @param int   $index Index of the file
     * @param mixed $limit Optional numeric limit (defaults to configured limit)
     * @return int Page to display for given index
     */
    protected function getPageForIndex($index, $limit = null)
    {
        $limit = $limit ?: $this->limit;
        return ceil($index / $limit);
    }
}