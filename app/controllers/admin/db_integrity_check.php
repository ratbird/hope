<?
require_once 'app/controllers/authenticated_controller.php';

class Admin_DbIntegrityCheckController extends AuthenticatedController
{
    public function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);

        $GLOBALS['perm']->check('root');

        PageLayout::setTitle(_('Überprüfen der Datenbank-Integrität'));
        Navigation::activateItem('/tools/db_integrity_new');

        $this->set_layout($GLOBALS['template_factory']->open('layouts/base_without_infobox'));

        $plugins = words('User Seminar Institut Archiv Studiengang');

        $this->checks = array();
        foreach ($plugins as $plugin) {
            $file = sprintf('%s/%s/IntegrityCheck%s.class.php',
                            $GLOBALS['STUDIP_BASE_PATH'],
                            $GLOBALS['RELATIVE_PATH_ADMIN_MODULES'],
                            $plugin);
            $class = basename($file, '.class.php');

            require_once $file;
            $this->checks[$plugin] = new $class;
        }
    }

    public function index_action()
    {
        // All variables are being defined in the before filter
    }
    
    public function show_action($check, $id)
    {
        $this->check  = $check;
        $this->id     = $id;
        $this->plugin = $this->checks[$check];

        $db = $this->plugin->getCheckDetailResult($id);

        $this->header = array();
        foreach ($db->metadata() as $column) {
            $this->header[] = $column['name'];
        }

        $this->rows = array();
        while ($db->next_record()) {
            $this->rows[] = $db->Record;
        }
    }
    
    public function check_action($check, $action = null, $id = false)
    {
        if (!isset($this->checks[$check])) {
            throw new InvalidArgumentException('Unknown integrity check "' . $check . '" called');
        }
        $plugin = $this->checks[$check];
        
        $data = array();
        for ($i = 0; $i < $plugin->getCheckCount(); $i += 1) {
            $data[$i] = array(
                'table' => $plugin->getCheckDetailTable($i),
                'count' => $plugin->doCheck($i)->num_rows(),
            );
        }

        $this->check  = $check;
        $this->data   = $data;

        if ($action === 'delete') {
            if ($confirmation = Request::option('confirmed')) {
                if ($confirmation === 'no') {
                    $this->redirect('check/' . $check);
                } else {
                    $result = $plugin->doCheckDelete($id);
                    if ($result === false) {
                        $mbox = Messagebox::error(_('Beim Löschen der Datensätze trat ein Fehler auf!'));
                    } else {
                        $message = sprintf(_('Es wurden %s Datensätze der Tabelle <b>%s</b> gelöscht!'),
                                           $result,
                                           $plugin->getCheckDetailTable($id));
                        $mbox = Messagebox::success($message);
                    }
                    PageLayout::postMessage($mbox);

                    $this->redirect('check/' . $check);
                }
            } else {
                $this->delete = $id;
            }
        }
    }
    
    public function url_for($to)
    {
        $arguments = func_get_args();
        array_unshift($arguments, 'admin/db_integrity_check');
        $arguments = explode('/', implode('/', $arguments));
        return call_user_func_array('parent::url_for', $arguments);
    }
}
