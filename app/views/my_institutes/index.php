<? if (isset($flash['decline_inst'])) : ?>
    <?=
    createQuestion(sprintf(_('Wollen Sie sich aus dem/der %s wirklich austragen?'),
            htmlReady($flash['name'])), array('cmd' => 'kill', 'studipticket' => $flash['studipticket']),
        array('cmd'          => 'back',
              'studipticket' => $flash['studipticket']),
        $controller->url_for(sprintf('my_institutes/decline_inst/%s', $flash['inst_id']))); ?>
<? endif ?>

<? if (empty($institutes)) : ?>
    <? if (!$GLOBALS['ALLOW_SELFASSIGN_INSTITUTE'] || $GLOBALS['perm']->have_perm("dozent")) : ?>
        <?=
        MessageBox::info(sprintf(_('Sie wurden noch keinen Einrichtungen zugeordnet. Bitte wenden Sie sich an einen der zuständigen %sAdministratoren%s.'),
            '<a href="' . URLHelper::getLink('dispatch.php/siteinfo/show') . '">', '</a>'))?>
    <? else : ?>
        <?=
        MessageBox::info(sprintf(_('Sie haben sich noch keinen Einrichtungen zugeordnet.
           Um sich Einrichtungen zuzuordnen, nutzen Sie bitte die entsprechende %sOption%s unter "Nutzerdaten - Studiendaten"
           auf Ihrer persönlichen Einstellungsseite.'), '<a href="' . URLHelper::getLink('dispatch.php/settings/studies#einrichtungen') . '">', '</a>'))?>
    <? endif ?>
<? else : ?>
    <? SkipLinks::addIndex(_('Meine Einrichtungen'), 'my_institutes') ?>
    <table class="default" id="my_institutes">
        <caption><?= _('Meine Einrichtungen') ?></caption>
        <colgroup>
            <col width="10px">
            <col width="25px">
            <col>
            <col width="<?= $nav_elements * 24 ?>px">
            <col width="45px">
        </colgroup>
        <thead>
        <tr>
            <th></th>
            <th></th>
            <th><?= _("Name") ?></th>
            <th style="text-align: center"><?= _("Inhalt") ?></th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        <? foreach ($institutes as $values) : ?>
            <? $lastVisit = $values['visitdate']; ?>
            <? $instid = $values['institut_id'] ?>
            <tr>
                <td style="width:1px"></td>
                <td>
                    <?=
                    (InstituteAvatar::getAvatar($instid)->getImageTag(Avatar::SMALL, tooltip2(htmlReady($values['name']))) != '' ? Assets::img('icons/20/blue/institute.png', tooltip2(htmlReady($values['name']))) :
                        InstituteAvatar::getAvatar($instid)->getImageTag(Avatar::SMALL, tooltip2(htmlReady($values['name'])))) ?>
                </td>

                <td style="text-align: left">
                    <a href="<?= URLHelper::getLink('dispatch.php/institute/overview', array('auswahl' => $instid)) ?>">
                        <?= htmlReady($GLOBALS['INST_TYPE'][$values["type"]]["name"] . ": " . $values["name"]) ?>
                    </a>
                </td>

                <td style="text-align: left; white-space: nowrap">
                    <? if (!empty($values['navigation'])) : ?>
                        <? foreach (MyRealmModel::array_rtrim($values['navigation']) as $key => $nav)  : ?>
                            <? if (isset($nav) && $nav->isVisible(true)) : ?>
                                <? $image = $nav->getImage(); ?>
                                <a href="<?=
                                UrlHelper::getLink('dispatch.php/institute/overview',
                                    array('auswahl'     => $instid,
                                          'redirect_to' => strtr($nav->getURL(), '?', '&'))) ?>" <?= $nav->hasBadgeNumber() ? 'class="badge" data-badge-number="' . intval($nav->getBadgeNumber()) . '"' : '' ?>>
                                    <?= Assets::img($image['src'], array_map("htmlready", $image)) ?>
                                </a>
                            <? elseif (is_string($key)) : ?>
                                <?= Assets::img('blank.gif', array('widtd' => 20, 'height' => 20)); ?>
                            <? endif ?>
                        <? endforeach ?>
                    <? endif ?>
                </td>

                <td style="text-align: left; white-space: nowrap">
                    <? if ($GLOBALS['ALLOW_SELFASSIGN_INSTITUTE'] && $values['perms'] == 'user') : ?>
                        <a href="<?=$controller->url_for('my_institutes/decline_inst/'.$instid)?>">
                            <?= Assets::img('icons/20/grey/door-leave.png', tooltip2(_("aus der Einrichtung austragen"))) ?>
                        </a>
                    <? else : ?>
                        <?= Assets::img('blank.gif', array('size' => '20')) ?>
                    <? endif ?>
                </td>
            </tr>
        <? endforeach ?>
        </tbody>
    </table>
<? endif ?>


<?php
$sidebar = Sidebar::Get();
$sidebar->setImage('sidebar/institute-sidebar.png');
$sidebar->setTitle(_('Meine Einrichtungen'));

$links = new ActionsWidget();
if ($reset) {
    $links->addLink(_('Alles als gelesen markieren'),
                    $controller->url_for('my_institutes/tabularasa/' . time()),
                    'icons/16/blue/accept.png');
}
if ($GLOBALS['perm']->have_perm('dozent') && !empty($institutes)) {
    $links->addLink(_('Einrichtungsdaten bearbeiten'),
                    URLHelper::getLink('dispatch.php/settings/statusgruppen'),
                    'icons/16/blue/edit/institute.png' );
}
if ($GLOBALS['perm']->have_perm('autor')) {
    $links->addLink(_('Einrichtungen suchen'),
                    URLHelper::getLink('institut_browse.php'),
                    'icons/16/blue/add/institute.png' );
    $links->addLink(_('Studiendaten bearbeiten'),
                    URLHelper::getLink('dispatch.php/settings/studies'),
                    'icons/16/blue/person.png');
}
$sidebar->addWidget($links);