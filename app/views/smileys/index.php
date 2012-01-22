<?php
use Studip\Button;

// divide smiley array in equal chunks, spillover from left to right
$columns   = 3;
$count     = count($smileys);

$max       = floor($count / $columns);
$spillover = $count % $columns;

$data = array();
for ($i = 0; $i < $columns; $i++) {
    $num = $max + (int)($spillover > 0);

    $data[] = array_splice($smileys, 0, $num);

    $spillover -= 1;
}
$data = array_filter($data);    
?>
<html>
<head>
    <title><?= _('Smiley-Übersicht') ?> (<?= $statistics['count_all'] ?>)</title>
<? // TODO Replace with PageLayout functionality? ?>
    <?= Assets::stylesheet('style.css') ?>
    <?= Assets::stylesheet('header.css') ?>
    <?= Assets::stylesheet('smiley.css') ?>
    <?= Assets::script('jquery-1.7.js') ?>
    <?= Assets::script('jquery-ui-1.8.14.custom.min.js') ?>
    <?= Assets::script('jquery.metadata.js') ?>
    <?= Assets::script('jquery.placehold-0.3.js') ?>
    <?= Assets::script('validator.min.js') ?>
    <?= Assets::script('application.js') ?>
    <?= Assets::script('smiley.js') ?>
    
</head>
<body class="smiley-popup">
    <div id="header">
        <div id="barTopFont">
            <?= _('Smiley-Übersicht') ?> -
            <?= sprintf(_('%s Smileys vorhanden'), $statistics['count_all']); ?>
        </div>
    </div>
    <? if ($GLOBALS['auth']->auth['jscript']): ?>
    <div id="barTopStudip">
        <?= Button::create('Fenster schliessen', array('onclick' => 'window.close()')) ?>
    </div>
    <? endif; ?>
    <div id="layout_page">
        <ul id="tabs" role="navigation">
        <? if ($favorites_activated): ?>
            <li <?= $view == 'favorites' ? 'class="current"' : '' ?>>
                <a href="<?= $controller->url_for('smileys/index/favorites') ?>">
                    <?= Assets::img('icons/16/black/smiley.png', array('class' => 'text-top')) ?>
                    <?= _('Favoriten') ?>
                </a>
            </li>
        <? endif; ?>
        <? if (Smiley::getShort()): ?>
            <li <?= $view == 'short' ? 'class="current"' : '' ?>>
                <a href="<?= $controller->url_for('smileys/index/short') ?>"><?= _('Kürzel') ?></a>
            </li>
        <? endif; ?>
            <li <?= $view == 'all' ? 'class="current"' : '' ?>>
                <a href="<?= $controller->url_for('smileys/index/all') ?>"><?= _('Alle') ?></a>
            </li>
        <? foreach (array_keys($characters) as $char): ?>
            <li <?= $view == $char ? 'class="current"' : '' ?>>
                <a href="<?= $controller->url_for('smileys/index', $char) ?>"><?= strtoupper($char) ?></a>
            </li>
        <? endforeach; ?>
        </ul>

        <div class="clear"></div>

        <div id="layout_container">
        <? if (!$count): ?>
            <strong>
                <?= $view == 'favorites'
                  ? _('Keine Favoriten vorhanden.')
                  : _('Keine Smileys vorhanden.') ?>
            </strong>
        <? else: ?>
            <table align="center" width="100%">
                <tr>
                <? foreach ($data as $smileys): ?>
                    <td valign="top" align="center">
                        <table class="default">
                            <thead>
                                <tr>
                                    <th><?= _('Bild') ?></th>
                                    <th><?= _('Schreibweise') ?></th>
                                    <th><?= _('Kürzel') ?></th>
                                <? if ($SMILEY_COUNTER): ?>
                                    <th>&Sigma;</th>
                                <? endif; ?>
                                <? if ($favorites_activated): ?>
                                    <th><?= _('Favorit') ?></th>
                                <? endif; ?>
                                </tr>
                            </thead>

                        <? foreach ($smileys as $smiley): ?>
                            <tr id="smiley<?= $smiley->id ?>" align="center"
                                class="<?= TextHelper::cycle('cycle_even', 'cycle_odd') ?>">
                                <td>
                                    <a name="smiley<?= $smiley->id ?>"></a>
                                    <?= $smiley->getImageTag() ?>
                                </td>
                                <td><?= sprintf(':%s:', $smiley->name) ?></td>
                                <td><?= htmlReady($smiley->short) ?></td>
                            <? if ($SMILEY_COUNTER): ?>
                                <td class="smiley_th">
                                    <?= $smiley->counter + $smiley->short_count ?>
                                </td>
                            <? endif; ?>
                            <? if ($favorites_activated): ?>
                                <td>
                                    <a href="<?= $controller->url_for('smileys/favor', $smiley->id, $view) ?>"
                                       class="smiley-toggle <?= $favorites->contain($smiley->id) ? 'favorite' : '' ?>">
                                    <? if ($favorites->contain($smiley->id)): ?>
                                        <?= _('Als Favorit entfernen') ?>
                                    <? else: ?>
                                        <?= _('Als Favorit markieren') ?>
                                    <? endif; ?>
                                    </a>
                                </td>
                            <? endif; ?>
                            </tr>
                        <? endforeach; ?>
                        </table>

                    </td>
                <? endforeach; ?>
                </tr>
            </table>
        <? endif; ?>
        </div>

    </div>

</body>
</html>
