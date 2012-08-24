<?
# Lifter010: TODO
?>
<? if (isset($flash['error'])): ?>
    <?= MessageBox::error($flash['error'], $flash['error_detail']) ?>
<? elseif (isset($flash['message'])): ?>
    <?= MessageBox::info($flash['message']) ?>
<? elseif (isset($flash['success'])): ?>
    <?= MessageBox::info($flash['success']) ?>
<? endif; ?>
 <h3>
    <?= _('Verwaltung von Systemkonfigurationen')?>
</h3>
<? if (!empty($search_filter)) : ?>
<table id="config_table" class="default collapsable">
    <tbody>
        <tr>
            <th><?=_("Name")?></th>
            <th><?=_("Wert")?></th>
            <th width="40%" ><?=_("Beschreibung")?></th>
            <th><?= _('Aktion') ?></th>
        </tr>
        <?foreach ($search_filter as $config): ?>
            <tr class="<?= TextHelper::cycle('hover_odd', 'hover_even') ?>">
                <td>
                    <?=$config['field']?>
                </td>
                <td>
                    <? if ($config['type'] == 'string'): ''?><em><?= htmlReady($config['value'])?></em>
                    <? elseif ($config['type'] == 'integer'): ''?> <?= htmlReady($config['value'])?>
                    <? elseif ($config['type'] == 'boolean'): ''?>
                        <?if ($config["value"]):?><?= Assets::img('icons/16/green/accept.png', array('title' => _('TRUE'))) ?>
                        <? else :?> <?= Assets::img('icons/16/red/decline.png', array('title' => _('FALSE'))) ?>
                        <? endif; ?>
                    <? endif; ?>
                </td>
                <td>
                    <?=$config['description']?>
                </td>
                <td align="right">
                    <a class="load-in-new-row" href="<?=$controller->url_for('admin/configuration/edit_configuration/'.$config['config_id'])?>">
                    <?= Assets::img('icons/16/blue/edit.png', array('title' => 'Konfigurationsparameter bearbeiten')) ?>
                    </a>
                </td>
            </tr>
        <?endforeach; ?>
</tbody>
</table>
<? else : ?>
<?= MessageBox::info(_('Es wurden keine Ergebnisse gefunden. Bitte probieren Sie einen anderen Begriff.'))?>
<?endif ; ?>

<?
$infobox_content = array(
    array(
        'kategorie' => _('Aktionen:'),
        'eintrag'   => array(
            array(
                "icon" => "icons/16/black/admin.png",
                "text" => '<a href="'.$controller->url_for('admin/configuration/configuration').'">'._('Zurück zur Konfiguration').'</a>'
            ),
            array(
                "icon" => "icons/16/black/person.png",
                "text" => '<a href="'.$controller->url_for('admin/configuration/user_configuration').'">'._('Nutzerparameter abrufen').'</a>'
            )
        )
    ), array(
        'kategorie' => _('Suche:'),
        'eintrag'   => array(
            array(
                'icon' => "icons/16/black/arr_2right.png",
                'text' => $this->render_partial('admin/configuration/results_filter', compact('search_filter', 'config_filter'))
        )
       )
    ), array(
        'kategorie' => _('Hinweise:'),
        'eintrag'   => array(
            array(
                        "text" => _("Sie können hier Parameter der Systemkonfiguration
                        direkt verändern. Sie können sowohl auf System- als auch Nutzervariablen zugreifen."),
                        "icon" => "icons/16/black/info.png"
                        )
        )
    )
);

$infobox = array('picture' => 'infobox/config.jpg', 'content' => $infobox_content);
?>
