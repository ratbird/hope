<? if (isset($flash['error'])): ?>
    <?= MessageBox::error($flash['error'], $flash['error_detail']) ?>
<? elseif (isset($flash['message'])): ?>
    <?= MessageBox::info($flash['message']) ?>
<? elseif (isset($flash['success'])): ?>
    <?= MessageBox::info($flash['success']) ?>
<? endif; ?>

<? if (!empty($search_users)) : ?>
<h3>
    <?= _('Vorhanden Konfigurationsparameter für den Nutzer: ').$search_users[0]['fullname'] ?>
</h3>
<table class="default">
    <tr>
        <th><?=_("Name")?></th>
        <th><?=_("Wert")?></th>
        <th><?=_("Typ")?></th>
        <th width="40%" ><?=_("Beschreibung")?></th>
        <th></th>
    </tr>
    <? foreach ($search_users as $search_user): ?>
    <tr class="<?= TextHelper::cycle('cycle_odd', 'cycle_even') ?>">
        <td>
            <?= htmlReady($search_user['field'])?>
        </td>
        <td>
            <? if ($search_user['type'] == 'string'): ''?><em><?= htmlReady($search_user['value'])?></em>
            <? elseif ($search_user['type'] == 'integer'): ''?> <?= htmlReady($search_user['value'])?>
            <? elseif ($search_user['type'] == 'boolean'): ''?>
                <?if ($search_user["value"] == '1'):?><?= Assets::img('haken_transparent.gif', array('title' => _('TRUE'))) ?>
                <? else :?> <?= Assets::img('x_transparent.gif', array('title' => _('FALSE'))) ?>
                <? endif; ?>
            <? endif; ?>
        </td>
        <td>
            <?=$search_user['type']?>
        </td>
        <td>
            <?= htmlReady($search_user['description'])?>
        </td>
        <td>
            <a class="load-in-new-row" href="<?=$controller->url_for('admin/configuration/edit_user_config/'.$user_id.'/'.$search_user['field'])?>">
            <?= Assets::img('edit_transparent.gif', array('title' => 'Konfigurationsparameter bearbeiten')) ?>
            </a>
        </td>
    </tr>
    <? endforeach; ?>
<? endif;?>
<? if ($give_alls): ''?>
<h3>
    <?= _('Globale Konfigurationsparameter für alle Nutzer')?>
</h3>
<table class="default">
    <tr>
        <th><?=_("Name")?></th>
        <th><?=_("Wert")?></th>
        <th><?=_("Typ")?></th>
        <th width="40%" ><?=_("Beschreibung")?></th>
        <th></th>
    </tr>
    <? foreach ($give_alls as $give_all): ?>
    <tr class="<?= TextHelper::cycle('cycle_odd', 'cycle_even') ?>">
    	<td>
            <?= htmlReady($give_all['field'])?>
        </td>
        <td>
            <? if ($give_all['type'] == 'string'): ''?><em><?= htmlReady($give_all['value'])?></em>
            <? elseif ($give_all['type'] == 'integer'): ''?> <?= htmlReady($give_all['value'])?>
            <? elseif ($give_all['type'] == 'boolean'): ''?>
                <?if ($give_all["value"]):?><?= Assets::img('haken_transparent.gif', array('title' => _('TRUE'))) ?>
                <? else :?> <?= Assets::img('x_transparent.gif', array('title' => _('FALSE'))) ?>
                <? endif; ?>
            <? endif; ?>
        </td>
        <td>
            <?=$give_all['type']?>
        </td>
        <td>
            <?= htmlReady($give_all['description'])?>
        </td>
        <td>
            <a class="load-in-new-row" href="<?=$controller->url_for('admin/configuration/edit_configuration/'.$give_all['config_id'])?>">
            <?= Assets::img('edit_transparent.gif', array('title' => 'Konfigurationsparameter bearbeiten')) ?>
            </a>
        </td>
    </tr>
    <? endforeach; ?>
<? endif;?>
    <tr>
        <td>
            <a href="<?=$controller->url_for('admin/configuration/configuration')?>"><?= makebutton('abbrechen', 'img', _('Zurück zur Übersicht'))?></a>
        </td>
    </tr>
</table>

<?
$infobox_content = array(
    array(
        'kategorie' => _('Aktionen:'),
        'eintrag'   => array(
            array(
                "text" => '<a href="'.$controller->url_for('admin/configuration/configuration').'">'._('Konfiguration').'</a>',
                "icon" => "icon-cont.gif"
            ), array(
                "text" => '<a href="'.$controller->url_for('admin/configuration/user_configuration/'.'giveAll').'">'._('Alle USER-Parameter').'</a>',
                "icon" => "icon-cont.gif"
            )
          )
    ), array(
        'kategorie' => _('Eingabe:'),
        'eintrag'   => array(
            array(
                "icon" => "suchen.gif",
                "text" =>  $this->render_partial('admin/configuration/user_filter', compact('allconfigs', 'config_filter'))
            )
        )
    ),
    array(
        'kategorie' => _('Hinweise:'),
        'eintrag'   => array(
            array(
                "text" => _("Geben Sie zur Suche den Vor-, Nach- oder Usernamen ein."),
                "icon" => "ausruf_small2.gif"
                ),
            array(
                "text" => _("USER-Parameter: Parameter die in der Tabelle config mit der Variabel -user- in der -range- Spalte versehen sind."),
                "icon" => "ausruf_small2.gif"
                )
        )
    )
);

$infobox = array('picture' => 'infoboxes/config.jpg', 'content' => $infobox_content);
?>
