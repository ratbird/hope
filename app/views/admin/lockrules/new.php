<?
# Lifter010: TODO
?>
<h3>
    <?=_("Neue Sperrebene eingeben für den Bereich:")?>
    &nbsp;
    <?=$rule_type_names[$lock_rule_type];?>
</h3>
<?
echo $message;
echo $this->render_partial('admin/lockrules/_form.php', array('action' => $this->controller->url_for('admin/lockrules/new')));

$sidebar = Sidebar::Get();
$sidebar->setTitle(_('Sperrebenen'));
$sidebar->setImage('sidebar/lock-sidebar.png');
$actions = new ActionsWidget();
$actions->addLink(_("Bearbeiten abbrechen"), $controller->url_for('admin/lockrules'), 'icons/16/blue/remove.png');
$sidebar->addWidget($actions);