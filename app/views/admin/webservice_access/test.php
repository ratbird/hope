<? use Studip\Button, Studip\LinkButton; ?>
<h3><?=_("Testen der Zugriffsregeln")?></h3>
<form action="<?=$controller->url_for('admin/webservice_access/test')?>" method="post">
<?=CSRFProtection::tokenTag()?>
<table class="default">
  <tr class="<?= TextHelper::cycle('table_row_even', 'table_row_odd') ?>">
  <td style="width:200px;"><?= _('API KEY') ?></td>
  <td><input type="text" name="test_api_key" size="50" required value="<?=htmlReady(Request::get("test_api_key"))?>"></td>
  </tr>
  <tr class="<?= TextHelper::cycle('table_row_even', 'table_row_odd') ?>">
  <td><?= _('Methode') ?></td>
  <td><input type="text" name="test_method" size="50" required value="<?=htmlReady(Request::get("test_method"))?>"></td>
  </tr>
  <tr class="<?= TextHelper::cycle('table_row_even', 'table_row_odd') ?>">
  <td><?= _('IP Adresse') ?></td>
  <td><input type="text" name="test_ip" size="50" required value="<?=htmlReady(Request::get("test_ip"))?>"></td>
  </tr>
  <tr>
  <td style="text-align:center" colspan="2">
  <?= Button::createAccept(_('Abschicken'), 'ok', array('title' => _('Test starten')))?>
  <?= LinkButton::createCancel(_('Abbrechen'), $controller->url_for('admin/webservice_access'), array('title' => _('Test abbrechen')))?>     
  </a>
  </td>
  </tr>
</table>
</form>
<?
$sidebar = Sidebar::Get();
$sidebar->setImage(Assets::image_path('sidebar/admin-sidebar.png'));
$sidebar->setTitle(_('Webservices'));

$actions = new ActionsWidget();
$actions->addLink(_('Liste der Zugriffsregeln'),$controller->url_for('admin/webservice_access'),'icons/16/blue/add.png');
$actions->addLink(_('Neue Zugriffsregel anlegen'),$controller->url_for('admin/webservice_access/new'),'icons/16/blue/add.png');

$sidebar->addWidget($actions);
