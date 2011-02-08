<?
# Lifter010: TODO
?>
<h3><?=_("Liste der Zugriffsregeln")?></h3>
<form action="<?=$controller->url_for('admin/webservice_access/update#edit')?>" method="post">
<?=CSRFProtection::tokenTag()?>
<table class="default">
<tr>
  <th style="width: 30%;">
    <?= _('API KEY') ?>
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
  <tr class="<?= TextHelper::cycle('steel1', 'steelgraulight') ?>">
    <? if (isset($edit) && $edit == $rule->id) :?>
        <td>
            <a name="edit"></a>
            <input name="ws_rule_id" type="hidden" value="<?=$rule->id?>">
            <input name="ws_rule_api_key" style="width:90%" type="text" value="<?= htmlReady($rule->api_key) ?>">
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
        <?=makeButton("ok", "input", "", "ok")?>
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
	          <?= Assets::img('icons/16/blue/trash.png', array('title' => _('löschen'))) ?>
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
        );

$infobox = array('picture' => 'infobox/administration.jpg', 'content' => $infobox_content);