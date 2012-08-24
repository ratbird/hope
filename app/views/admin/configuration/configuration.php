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
    <?= _('Verwaltung von Systemkonfigurationen') ?>
</h3>
<table id="config_table" class="default collapsable">
    <colgroup>
        <col width="35%" />
        <col width="8%" />
        <col width="8%" />
        <col width="41%" />
        <col width="8%" />
    </colgroup>
    <thead>
        <tr>
            <th><?= _('Name') ?></th>
            <th><?= _('Wert') ?></th>
            <th><?= _('Typ') ?></th>
            <th><?= _('Beschreibung') ?></th>
            <th><?= _('Aktion') ?></th>
        </tr>
    </thead>
<? $outer_index = 1; foreach ($allconfigs as $section => $config): ?>
    <tbody <?= ((!is_null($current_section) and $current_section == $section) or !is_null($config_filter)) ? '': 'class="collapsed"' ?>>
        <tr class="steel header-row">
            <td class="toggle-indicator" colspan="5">
                <a class="toggler" href="<?= $controller->url_for('admin/configuration/configuration/'.$section) ?>">
                    <?= empty($section) ? '- '._(' Ohne Kategorie ').' -' : $section ?>
                    (<?=count($config['data'])?>)
                </a>
            </td>
        </tr>
    <? foreach ($config['data'] as $index => $conf): ?>
        <tr class="<?= TextHelper::cycle('hover_odd', 'hover_even') ?>">
            <td class="label-cell">
                <?=$conf['field']?>
            </td>
            <td>
            <? if ($conf['type'] == 'string'): ?>
                <em><?= htmlReady($conf['value'])?></em>
            <? elseif ($conf['type'] == 'integer'): ?>
                <?= htmlReady($conf['value'])?>
            <? elseif ($conf['type'] == 'boolean'): ?>
                <?if ($conf["value"]):?>
                    <?= Assets::img('icons/16/green/accept.png', array('title' => _('TRUE'))) ?>
                <? else :?>
                    <?= Assets::img('icons/16/red/decline.png', array('title' => _('FALSE'))) ?>
                <? endif; ?>
            <? endif; ?>
            </td>
            <td><?= $conf['type'] ?></td>
            <td><?= htmlReady($conf['description']) ?></td>
            <td align="right">
                <a class="load-in-new-row" href="<?=$controller->url_for('admin/configuration/edit_configuration/'.$conf['config_id'])?>">
                <?= Assets::img('icons/16/blue/edit.png', array('title' => 'Konfigurationsparameter bearbeiten')) ?></a>
            </td>
        </tr>
    <? endforeach; ?>
    </tbody>
<? $outer_index++; endforeach; ?>
</table>

<?
$infobox = array('picture' => 'infobox/config.jpg', 'content' => array(
    array(
        'kategorie' => _('Aktionen:'),
        'eintrag'   => array(
            array(
                "icon" => "icons/16/black/person.png",
                "text" => '<a href="'.$controller->url_for('admin/configuration/user_configuration').'">'._('Nutzerparameter abrufen').'</a>'
            )
        )
    ),
    array(
        'kategorie' => _('Anzeigefilter:'),
        'eintrag'   => array(
            array(
                "icon" => "icons/16/black/arr_2right.png",
                "text" => $this->render_partial('admin/configuration/config_filter', compact('allsections', 'config_filter'))
            )
        )
    ),
    array(
        'kategorie' => _('Suche:'),
        'eintrag'   => array(
            array(
                "icon" => "icons/16/black/search.png",
                "text" =>  $this->render_partial('admin/configuration/results_filter', compact('search_filter', 'config_filter'))
            )
        )
    ),
    array(
        'kategorie' => _('Hinweise:'),
        'eintrag'   => array(
            array(
                "icon" => "icons/16/black/info.png",
                "text" => _("Sie können hier Parameter der Systemkonfiguration direkt verändern. Sie können sowohl auf System- als auch Nutzervariablen zugreifen.")
            )
        )
    )
));
