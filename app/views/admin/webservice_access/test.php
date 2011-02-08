<h3><?=_("Testen der Zugriffsregeln")?></h3>
<form action="<?=$controller->url_for('admin/webservice_access/test')?>" method="post">
<?=CSRFProtection::tokenTag()?>
<table class="default">
  <tr class="<?= TextHelper::cycle('steel1', 'steelgraulight') ?>">
  <td><?= _('API KEY') ?></td>
  <td><input type="text" name="test_api_key" value="<?=htmlReady(Request::get("test_api_key"))?>"></td>
  </tr>
  <tr class="<?= TextHelper::cycle('steel1', 'steelgraulight') ?>">
  <td><?= _('Methode') ?></td>
  <td><input type="text" name="test_method" value="<?=htmlReady(Request::get("test_method"))?>"></td>
  </tr>
  <tr class="<?= TextHelper::cycle('steel1', 'steelgraulight') ?>">
  <td><?= _('IP Adresse') ?></td>
  <td><input type="text" name="test_ip" value="<?=htmlReady(Request::get("test_ip"))?>"></td>
  </tr>
  <tr class="<?= TextHelper::cycle('steel1', 'steelgraulight') ?>">
  <td colspan="2" ><?=makeButton('abschicken', 'input', _("Test starten"), 'ok')?></td>
  </tr>
</table>
</form>
<?
$infobox_content = array(
            array(
                'kategorie' => _('Zugriffsregeln verwalten'),
                'eintrag'   => array(array(
                'icon' => 'icons/16/black/plus.png',
                'text' => '<a href="'.$controller->url_for('admin/webservice_access').'">'._('Liste der Zugriffsregeln').'</a>'
            ),
            array(
                    'icon' => 'icons/16/black/plus.png',
                    'text' => '<a href="'.$controller->url_for('admin/webservice_access/new').'">'._('Neue Zugriffsregel anlegen').'</a>'
                ))
            ),
        );

$infobox = array('picture' => 'infobox/administration.jpg', 'content' => $infobox_content);
