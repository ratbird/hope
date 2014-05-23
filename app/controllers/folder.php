<?php
require_once 'app/controllers/authenticated_controller.php';

class FolderController extends AuthenticatedController
{
    public function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);
        
        if (Request::isXhr()) {
            $this->set_layout(null);
            $this->response->add_header('Content-Type', 'text/html;charset=windows-1252');
        }
    }

    public function create_action($id, $type)
    {
        checkObject();
        checkObjectModule('documents');
        object_set_visit_module('documents');
        
        if (!$GLOBALS['rechte']) {
            throw new AccessDeniedException(_('Sie dürfen auf diesen Teil des Systems nicht zugreifen.'));
        }

        PageLayout::setTitle(_('Neuen Ordner erstellen'));
        
        $options = array();
        $options[md5('new_top_folder')]  = _('Namen auswählen oder wie Eingabe') . ' -->';
        
        $query = "SELECT SUM(1) FROM folder WHERE range_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($id));
        if ($statement->fetchColumn() == 0) {
            $options[$id] = _('Allgemeiner Dateiordner');
        }

        if ($type === 'sem'){
            $query = "SELECT statusgruppe_id AS id, statusgruppen.name AS name
                      FROM statusgruppen
                      LEFT JOIN folder ON (statusgruppe_id = folder.range_id)
                      WHERE statusgruppen.range_id = ? AND folder_id IS NULL
                      ORDER BY position";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($id));
            $statement->setFetchMode(PDO::FETCH_ASSOC);

            foreach ($statement as $row) {
                $options[$row['id']] = sprintf(_('Dateiordner der Gruppe: %s'), $row['name']);
            }

            $issues = array();
            $shown_dates = array();

            $query = "SELECT themen_termine.issue_id, termine.date, folder.name, termine.termin_id, date_typ
                      FROM termine
                      LEFT JOIN themen_termine USING (termin_id)
                      LEFT JOIN folder ON (themen_termine.issue_id = folder.range_id)
                      WHERE termine.range_id = ? AND folder.folder_id IS NULL
                      ORDER BY termine.date, name";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($id));
            $statement->setFetchMode(PDO::FETCH_ASSOC);

            foreach ($statement as $row) {
                if ($row['name']) {
                    continue;
                }

                $name = sprintf(_('Ordner für %s [%s]'),
                                date('d.m.Y', $row['date']),
                                $GLOBALS['TERMIN_TYP'][$row['date_typ']]['name']);

                if ($row['issue_id']) {
                    if (!$issues[$row['issue_id']]) {
                        $issues[$row['issue_id']] = new Issue(array('issue_id' => $row['issue_id']));
                    }
                    $name .= ', ' . my_substr($issues[$row['issue_id']]->toString(), 0, 20);
                    $option_id = $row['issue_id'];
                } else {
                    $option_id = $row['termin_id'];
                }

                $options[$option_id] = $name;
            }

        }
        $this->options = $options;
        $this->id      = $id;
    }
    
    public function after_filter($action, $args)
    {
        if (Request::isXhr()) {
            $this->response->add_header('X-Title', PageLayout::getTitle());
        }
    }
}
