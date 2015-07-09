<?
# Lifter010: TODO
?>
<h3><?=sprintf("Sperrebene \"%s\" ändern", htmlready($lock_rule["name"]))?></h3>
<?
echo $message;
echo $this->render_partial('admin/lockrules/_form.php', array('action' => $this->controller->url_for('admin/lockrules/edit/' . $lock_rule->getId())));

$sidebar = Sidebar::Get();
$sidebar->setTitle(_('Sperrebenen'));
$sidebar->setImage('sidebar/lock-sidebar.png');
$info = new ListWidget();
$info->setTitle(_('Informationen'));
$info->addElement(new WidgetElement( sprintf(_("Diese Sperrebene wird von %s Objekten benutzt."), $lock_rule->getUsage())));
$sidebar->addWidget($info);
$actions = new ActionsWidget();
$actions->addLink(_("Diese Ebene löschen"), $controller->url_for('admin/lockrules/delete/' . $lock_rule->getid()), 'icons/16/blue/trash.png');
$actions->addLink(_("Bearbeiten abbrechen"), $controller->url_for('admin/lockrules'), 'icons/16/blue/remove.png');
$sidebar->addWidget($actions);


