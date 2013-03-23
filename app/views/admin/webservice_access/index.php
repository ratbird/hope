<?
# Lifter010: TODO
?>
<h3><?=_("Liste der Zugriffsregeln")?></h3>
<form action="<?=$controller->url_for('admin/webservice_access/update#edit')?>" method="post">
<?=CSRFProtection::tokenTag()?>
<table class="default">
<tr>
  <th style="width: 30%;">
    <?= _('API-Key') ?>
  </th>
  <th style="width: 30%;">
    <?= _('Methode') ?>
  </th>
  <th style="width: 30%;">
    <?= _('IP Bereich') ?>
  </th>
  <th style="width: 5%;">
    <?= _('Typ') ?>
  </th>
  <th style="width: 5%;">
    <?= _('Aktion') ?>
  </th>
</tr>
<? foreach ($ws_rules as $rule): ?>
  <tr class="<?= TextHelper::cycle('table_row_even', 'table_row_odd') ?>">
    <? if (isset($edit) && $edit == $rule->id) :?>
        <td>
            <a name="edit"></a>
            <input name="ws_rule_id" type="hidden" value="<?=$rule->id?>">
            <input name="ws_rule_api_key" style="width:90%" type="text" required value="<?= htmlReady($rule->api_key) ?>">
        </td>
        <td>
            <input name="ws_rule_method" style="width:90%" type="text" value="<?= htmlReady($rule->method) ?>">
        </td>
        <td>
            <input name="ws_rule_ip_range" style="width:90%" type="text" value="<?= htmlReady($rule->ip_range) ?>">
        </td>
        <td>
            <select name="ws_rule_type">
            <option <?=($rule->type == 'allow' ? 'selected' : '') ?>>allow</option>
            <option <?=($rule->type == 'deny' ? 'selected' : '') ?>>deny</option>
            </select>
        </td>
        <td>
        <?= Assets::input("icons/16/green/accept.png", array('type' => "image", 'class' => "middle", 'name' => "ok", 'title' => _('�nderungen speichern'))) ?>
        <?= Assets::input("iicons/16/red/decline", array('type' => "image", 'class' => "middle", 'name' => "cancel", 'title' => _('Abbrechen'))) ?>
        </td>
    <? else : ?>
        <td>
            <?= htmlReady($rule->api_key) ?>
        </td>
        <td>
            <?= htmlReady($rule->method) ?>
        </td>
        <td>
            <?= htmlReady($rule->ip_range) ?>
        </td>
        <td>
            <?= htmlReady($rule->type) ?>
        </td>
        <td>
          <a href="<?= $controller->url_for('admin/webservice_access/edit/'.$rule->id.'#edit') ?>">
            <?= Assets::img('icons/16/blue/edit.png', array('title' => _('bearbeiten'))) ?>
          </a>
          <a href="<?= $controller->url_for('admin/webservice_access/delete/'.$rule->id) ?>">
              <?= Assets::img('icons/16/blue/trash.png', array('title' => _('l�schen'))) ?>
          </a>
        </td>
    <? endif;?>
  </tr>
<? endforeach ?>
</table>
</form>
<?

$infobox_content = array(
            array(
                'kategorie' => _('Zugriffsregeln verwalten'),
                'eintrag'   => array(array(
                'icon' => 'icons/16/black/plus.png',
                'text' => '<a href="'.$controller->url_for('admin/webservice_access/test').'">'._('Regeln testen').'</a>'
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
                'text' => '<div>' . _("Sie k�nnen f�r einen API-Key beliebig viele Regeln hinterlegen.") . '</div>
                           <div>' . _("Ein leerer Eintrag f�r die Methode gilt f�r alle Methoden. Sie k�nnen auch nur einen Teil eines Methodennamens eingeben.") . '</div>
                           <div>' . _("Ein leerer Eintrag f�r den IP Bereich gilt f�r alle Adressen. Sie k�nnen mehrere IP Adressen/Bereiche durch Kommata getrennt angeben. Adressbereiche m�ssen in CIDR Notation angegeben werden, z.B. 192.168.0.0/24") . '</div>'
                 ))
            )
        );

$infobox = array('picture' => 'infobox/administration.png', 'content' => $infobox_content);

