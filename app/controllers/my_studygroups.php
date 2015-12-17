<?php
class MyStudygroupsController extends AuthenticatedController
{
    public function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);
        if (!$GLOBALS['perm']->have_perm("root")) {
            Navigation::activateItem('/browse/my_studygroups/index');
        }
    }

    public function index_action()
    {
        PageLayout::setHelpKeyword("Basis.MeineStudiengruppen");
        PageLayout::setTitle(_("Meine Studiengruppen"));
        URLHelper::removeLinkParam('cid');
        $this->studygroups  = MyRealmModel::getStudygroups();
        $this->nav_elements = MyRealmModel::calc_single_navigation($this->studygroups);
        $this->set_sidebar();
    }


    public function set_sidebar() {
        $sidebar = Sidebar::Get();
        $sidebar->setImage('sidebar/studygroup-sidebar.png');
        $sidebar->setTitle(_('Meine Studiengruppen'));
        if(count($this->studygroups) > 0) {
            $setting_widget = new ActionsWidget();
            $setting_widget->setTitle(_("Aktionen"));
            $setting_widget->addLink(_('Farbgruppierung �ndern'),
                                     URLHelper::getLink('dispatch.php/my_courses/groups/all/true'), Icon::create('group4', 'clickable'),
                    array('data-dialog' => 'buttons=true'));
            $sidebar->addWidget($setting_widget);
        }

    }
}