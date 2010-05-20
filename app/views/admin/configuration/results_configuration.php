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
<table id="config_table" class="default collapsable">
    <tbody>
        <tr>
            <th><?=_("Name")?></th>
            <th></th>
            <th><?=_("Value")?></th>
            <th width="40%" ><?=_("Beschreibung")?></th>

        </tr>
        <? if (!empty($search_filter)): ''?>
            <?foreach ($search_filter as $config): ?>
                    <tr class="<?= TextHelper::cycle('cycle_odd', 'cycle_even') ?>">
                        <td>
                            <?=$config['field']?>
                        </td>
                        <td>
                            <a class="load-in-new-row" href="<?=$controller->url_for('admin/configuration/edit_configuration/'.$config['config_id'])?>">
                            <?= Assets::img('edit_transparent.gif', array('title' => 'Konfigurationsparameter bearbeiten')) ?>
                            </a>
                        </td>
                        <td>
                            <? if ($config['type'] == 'string'): ''?><em><?= htmlReady($config['value'])?></em>
                            <? elseif ($config['type'] == 'integer'): ''?> <?= htmlReady($config['value'])?>
                            <? elseif ($config['type'] == 'boolean'): ''?>
                                <?if ($config["value"]):?><?= Assets::img('haken_transparent.gif', array('title' => _('TRUE'))) ?>
                                <? else :?> <?= Assets::img('x_transparent.gif', array('title' => _('FALSE'))) ?>
                                <? endif; ?>
                            <? endif; ?>
                        </td>
                        <td>
                            <?=$config['description']?>
                        </td>
                    </tr>
            <?endforeach; ?>
        <?endif ; ?>
</tbody>
</table>

<?
$infobox_content = array(
    array(
        'kategorie' => _('Aktionen:'),
        'eintrag'   => array(
            array(
                "icon" => "icon-cont.gif",
                "text" => '<a href="'.$controller->url_for('admin/configuration/configuration').'">'._('Konfiguration').'</a>'
            ),
            array(
                "icon" => "icon-cont.gif",
                "text" => '<a href="'.$controller->url_for('admin/configuration/user_configuration').'">'._('Nutzerparameter abrufen').'</a>'
            )
        )
    ), array(
        'kategorie' => _('Suche:'),
        'eintrag'   => array(
            array(
                'icon' => 'suchen.gif',
                'text' =>  $this->render_partial('admin/configuration/results_filter', compact('search_filter', 'config_filter'))
        )
       )
    ), array(
        'kategorie' => _('Hinweise:'),
        'eintrag'   => array(
            array(
                        "text" => _("Sie können hier Parameter der Systemkonfiguration
                        direkt verändern. Sie können sowohl auf System- als auch Nutzervariablen zugreifen."),
                        "icon" => "ausruf_small2.gif"
                        )
        )
    )
);

$infobox = array('picture' => 'infoboxes/config.png', 'content' => $infobox_content);
?>
