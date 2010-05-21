<? if (isset($flash['error'])): ?>
    <?= MessageBox::error($flash['error'], $flash['error_detail']) ?>
<? elseif (isset($flash['message'])): ?>
    <?= MessageBox::info($flash['message']) ?>
<? elseif (isset($flash['success'])): ?>
    <?= MessageBox::info($flash['success']) ?>
<? endif; ?>
 <h3>
    <?= _('Verwaltung von Systemkonfigurationen') ?>
</h3>
<table id="config_table" class="default collapsable <?= is_null($config_filter) ? '' : 'open' ?>">
    <tr>
        <th><?= _('Name') ?></th>
        <th><?= _('Wert') ?></th>
        <th><?= _('Typ') ?></th>
        <th><?= _('Beschreibung') ?></th>
        <th><?= _('Aktion') ?></th>
    </tr>
    <? $outer_index = 1; foreach ($allconfigs as $section => $config): ?>
        <?php if (!is_null($config_filter) and $config_filter != $section) continue; ?>
        <tbody <?= ((!is_null($current_section) and $current_section == $section) or !is_null($config_filter)) ? 'class="open"': '' ?>>
            <tr class="steel">
                <td colspan="5">
                    <a class="toggler" href="#">
                    <? if (empty($section)) : $section = '-'._(' Ohne Kategorie ').'-'?><?= $section?> (<?=count($config['data'])?>)
                    <? else : ?><?= $section?> (<?=count($config['data'])?>)
                    <? endif;?>
                </td>
            </tr>
            <? foreach ($config['data'] as $index=>$conf): ?>
                <tr class="<?= TextHelper::cycle('cycle_odd', 'cycle_even') ?>">
                    <td>
                        <?=$conf['field']?>
                    </td>
                    <td>
                        <? if ($conf['type'] == 'string'): ''?><em><?= htmlReady($conf['value'])?></em>
                            <? elseif ($conf['type'] == 'integer'): ''?> <?= htmlReady($conf['value'])?>
                            <? elseif ($conf['type'] == 'boolean'): ''?>
                                <?if ($conf["value"]):?><?= Assets::img('haken_transparent.gif', array('title' => _('TRUE'))) ?>
                                <? else :?> <?= Assets::img('x_transparent.gif', array('title' => _('FALSE'))) ?>
                                <? endif; ?>
                        <? endif; ?>
                    </td>
                    <td >
                        <?=$conf['type']?>
                    </td>
                    <td>
                        <?=$conf['description']?>
                    </td>
                    <td align="right">
                        <a class="load-in-new-row" href="<?=$controller->url_for('admin/configuration/edit_configuration/'.$conf['config_id'])?>">
                        <?= Assets::img('edit_transparent.gif', array('title' => 'Konfigurationsparameter bearbeiten')) ?></a>
                    </td>
                </tr>
            <? endforeach; ?>
        </tbody>
    <? $outer_index++; endforeach; ?>
</table>

<?
$infobox_content = array(
    array(
        'kategorie' => _('Aktionen:'),
        'eintrag'   => array(
            array(
                "icon" => "icon-cont.gif",
                "text" => '<a href="'.$controller->url_for('admin/configuration/user_configuration').'">'._('Nutzerparameter abrufen').'</a>'
            )
        )
    ),
    array(
        'kategorie' => _('Anzeigefilter:'),
        'eintrag'   => array(
            array(
                "icon" => "suchen.gif",
                "text" => $this->render_partial('admin/configuration/config_filter', compact('allconfigs', 'config_filter'))
            )
       )
    ), array(
        'kategorie' => _('Suche:'),
        'eintrag'   => array(
            array(
                "icon" => "suchen.gif",
                "text" =>  $this->render_partial('admin/configuration/results_filter', compact('search_filter', 'config_filter'))
        )
       )
    ), array(
        'kategorie' => _('Hinweise:'),
        'eintrag'   => array(
            array(
                "icon" => "ausruf_small2.gif",
                "text" => _("Sie können hier Parameter der Systemkonfiguration direkt verändern. Sie können sowohl auf System- als auch Nutzervariablen zugreifen.")
                )
        )
    )
);

$infobox = array('picture' => 'infoboxes/config.jpg', 'content' => $infobox_content);
?>