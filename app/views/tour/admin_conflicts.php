<? use Studip\Button, Studip\LinkButton; ?>
<div class="help_content_admin">
<h2><?= _('Versions-Konflikte der Touren') ?></h2>
<table cellspacing="0" cellpadding="0" width="100%">
    <tr>
        <td width="100%" class="blank"><p class="info">
        <form action="<?=$controller->url_for('tour/admin_conflicts')?>" id="admin_tour_form" method="POST">
        <?=CSRFProtection::tokenTag(); ?>
        <? if (count($conflicts)) : ?>
            <? foreach($conflicts as $conflict) : ?>
            <table class="default">
                <? $keys = array_keys($conflict); ?>
                <colgroup>
                    <col width="20%">
                    <col width="40%">
                    <col width="40%">
                </colgroup>                   
                <tbody>
                    <tr>
                    <th><?=_("Feld")?></th>
                    <th><?=sprintf(_("Lokale Version (%s)"), $conflict[$keys[0]]->studip_version)?></th>
                    <th><?=sprintf(_("Offizielle Version (%s)"), $conflict[$keys[1]]->studip_version)?></th>
                    </tr>
                    <tr>
                    <td><?=_('Titel')?></td>
                    <td><?=$conflict[$keys[0]]->name?></td>
                    <td><?=$conflict[$keys[1]]->name?></td>
                    </tr>
                    <? foreach ($diff_fields as $field => $title) : ?>
                        <? if ($conflict[$keys[0]]->$field != $conflict[$keys[1]]->$field) : ?>
                            <tr>
                            <td><?=$title?></td>
                            <td><?=$conflict[$keys[0]]->$field?></td>
                            <td><?=$conflict[$keys[1]]->$field?></td>
                            </tr>
                        <? endif ?>
                    <? endforeach ?>
                    <? if (count($conflict[$keys[0]]->steps) > count($conflict[$keys[1]]->steps)) 
                        $max_steps = count($conflict[$keys[0]]->steps);
                    else
                        $max_steps = count($conflict[$keys[1]]->steps) ?>
                    <? for ($nr = 1; $nr <= $max_steps; $nr++) : ?>
                        <? foreach ($diff_step_fields as $field => $title) : ?>
                            <? if ($conflict[$keys[0]]->steps[$nr]->$field != $conflict[$keys[1]]->steps[$nr]->$field) : ?>
                                <tr>
                                <td><?=$title?> <?=sprintf(_('(Schritt %s)'), $nr)?></td>
                                <td><?=$conflict[$keys[0]]->steps[$nr]->$field?></td>
                                <td><?=$conflict[$keys[1]]->steps[$nr]->$field?></td>
                                </tr>
                            <? endif ?>
                        <? endforeach ?>
                    <? endfor ?>
                </tbody>
                <tfoot>
                <tr>
                    <td></td>
                    <td><?=LinkButton::create(_('Übernehmen'), $controller->url_for('tour/resolve_conflict/'.$conflict[$keys[0]]->getId().'/accept')) ?></td>
                    <td><?=LinkButton::create(_('Übernehmen'), $controller->url_for('tour/resolve_conflict/'.$conflict[$keys[0]]->getId().'/delete')) ?></td>
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
$widget->addLink(_('Übersicht'), URLHelper::getURL('dispatch.php/tour/admin_overview'));
$widget->addLink(_('Konflikte'), URLHelper::getURL('dispatch.php/tour/admin_conflicts'))->setActive(true);
$sidebar->addWidget($widget);