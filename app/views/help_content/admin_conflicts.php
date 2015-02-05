<? use Studip\Button, Studip\LinkButton; ?>
<div class="help_content_admin">
<h2><?= _('Versions-Konflikte der Hilfe-Texte') ?></h2>
<table cellspacing="0" cellpadding="0" width="100%">
    <tr>
        <td width="100%" class="blank"><p class="info">
        <form action="<?=$controller->url_for('help_content/admin_conflicts')?>" id="admin_help_content_form" method="POST">
        <?=CSRFProtection::tokenTag(); ?>
        <? if (count($conflicts)) : ?>
            <? foreach($conflicts as $conflict) : ?>
            <table class="default">
                <colgroup>
                    <col width="50%">
                    <col width="50%">
                </colgroup>                   
                <thead>
                    <? $keys = array_keys($conflict); ?>
                    <tr>
                    <th colspan="2"><?=_('Seite:')?> <?=$conflict[$keys[0]]->route?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                    <th><?=sprintf(_("Lokale Version (%s)"), $conflict[$keys[0]]->studip_version)?></th>
                    <th><?=sprintf(_("Offizielle Version (%s)"), $conflict[$keys[1]]->studip_version)?></th>
                    </tr>
                    <tr>
                    <td>
                        <?=$conflict[$keys[0]]->content?>
                    </td>
                    <td>
                        <?=$conflict[$keys[1]]->content?>
                    </td>
                    </tr>
                    <tr>
                    </tr>
                </tbody>
                <tfoot>
                <tr>
                    <td><?=LinkButton::create(_('Übernehmen'), $controller->url_for('help_content/resolve_conflict/'.$conflict[$keys[0]]->getId().'/accept')) ?></td>
                    <td><?=LinkButton::create(_('Übernehmen'), $controller->url_for('help_content/resolve_conflict/'.$conflict[$keys[0]]->getId().'/delete')) ?></td>
                </tr></tfoot>
            </table>
            <? endforeach ?>
        <? else : ?>
            <?=_('Keine Konflikte vorhanden.')?>
        <? endif ?>
        </form><br><br></p></td>
    </tr>
</table>
</div>
<?
$sidebar = Sidebar::get();
$widget = new ViewsWidget();
$widget->addLink(_('Übersicht'), URLHelper::getURL('dispatch.php/help_content/admin_overview'));
$widget->addLink(_('Konflikte'), URLHelper::getURL('dispatch.php/help_content/admin_conflicts'))->setActive(true);
$sidebar->addWidget($widget);