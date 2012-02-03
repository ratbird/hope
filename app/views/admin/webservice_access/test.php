<? use Studip\Button, Studip\LinkButton; ?>
<h3><?=_("Testen der Zugriffsregeln")?></h3>
<form action="<?=$controller->url_for('admin/webservice_access/test')?>" method="post">
<?=CSRFProtection::tokenTag()?>
<table class="default">
  <tr class="<?= TextHelper::cycle('steel1', 'steelgraulight') ?>">
  <td style="width:200px;"><?= _('API KEY') ?></td>
  <td><input type="text" name="test_api_key" size="50" required value="<?=htmlReady(Request::get("test_api_key"))?>"></td>
  </tr>
  <tr class="<?= TextHelper::cycle('steel1', 'steelgraulight') ?>">
  <td><?= _('Methode') ?></td>
  <td><input type="text" name="test_method" size="50" required value="<?=htmlReady(Request::get("test_method"))?>"></td>
  </tr>
  <tr class="<?= TextHelper::cycle('steel1', 'steelgraulight') ?>">
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
$infobox_content = array(
            array(
                'kategorie' => _('Zugriffsregeln testen'),
                'eintrag'   => array(array(
                'icon' => 'icons/16/black/plus.png',
                'text' => '<a href="'.$controller->url_for('admin/webservice_access').'">'._('Liste der Zugriffsregeln').'</a>'
            ),
            array(
                    'icon' => 'icons/16/black/plus.png',
                    'text' => '<a href="'.$controller->url_for('admin/webservice_access/new').'">'._('Neue Zugriffsregel anlegen').'</a>'
                ))
            ),
            array(
                'kategorie' => _('Hinweise'),
                'eintrag'   => array(array(
                'icon' => 'icons/16/black/info.png',
                'text' => _("Hier können Sie testen ob der Zugriff mit einem API-Key für eine konkrete Webservicemethode von einer bestimmten IP Adresse aus möglich ist.")
                 ))
            )
        );

$infobox = array('picture' => 'infobox/administration.png', 'content' => $infobox_content);
