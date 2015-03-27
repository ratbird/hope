<? use Studip\Button, Studip\LinkButton; ?>
<?=$delete_question?>
<div class="tour_admin">
<h2><?= _('Verwalten von Touren') ?></h2>
<table cellspacing="0" cellpadding="0" width="100%">
    <tr>
        <td width="100%" class="blank"><p class="info">
        <form action="<?=$controller->url_for('tour/admin_overview')?>" id="admin_tour_form" method="POST">
        <input type="hidden" name="tour_filter" value="set">
        <input type="hidden" name="tour_filter_term" value="<?=htmlReady($tour_searchterm)?>">
        <?=CSRFProtection::tokenTag(); ?>
        <table class="default">
            <? if ($filter_text) : ?>
                <tr><td colspan="1">
                <?=$filter_text?>
                </td><td><div class="tour_reset_filter">
                <?=Button::create(_('Auswahl aufheben'), 'reset_filter')?>
                </div>
                </td></tr>
            <? endif ?>
        </table>
        <? if (count($tours)) : ?>
            <table class="default">
                <caption>
                    <div class="tour_list_title"><?=_('Touren')?></div>
                </caption>
                <colgroup>
                    <col width="20">
                    <col>
                    <col width="10%">
                    <col width="10%">
                    <col width="20%">
                    <col width="10%">
                    <col width="80">
                </colgroup>                   
                <thead><tr>
                    <th><?=_("Aktiv")?></th>
                    <th><?=_("Überschrift")?></th>
                    <th><?=_("Typ")?></th>
                    <th><?=_("Zugang")?></th>
                    <th><?=_("Startseite")?></th>
                    <th><?=_("Anzahl der Schritte")?></th>
                    <th><?=_("Aktion")?></th>
                </tr></thead>
                <tbody>
                <? foreach ($tours as $tour_id => $tour) : ?>
                    <tr>
                    <td><input type="CHECKBOX" name="tour_status_<?=$tour_id?>" value="1" aria-label="<?= _('Status der Tour (aktiv oder inaktiv)')?>" <?=tooltip(_("Status der Tour (aktiv oder inaktiv)"),false)?><?=($tour->settings->active) ? ' checked' : ''?>></td>
                    <td><a href="<?=URLHelper::getURL('dispatch.php/tour/admin_details/'.$tour_id)?>">
                    <?=htmlReady($tour->name)?>
                    </a></td>
                    <td><?=$tour->type?></td>
                    <td><?=$tour->settings->access?></td>
                    <td><?=(count($tour->steps)) ? htmlReady($tour->steps[0]->route) : ''?></td>
                    <td><?=count($tour->steps)?></td>
                    <td>
                    <a href="<?=URLHelper::getURL('dispatch.php/tour/admin_details/'.$tour_id)?>" <?=tooltip(_('Tour bearbeiten'))?>>
                    <?= Assets::img('icons/16/blue/edit.png') ?></a>
                    <?= Assets::input('icons/16/blue/trash.png', tooltip2(_('Tour löschen')) + array(
                            'name' => 'tour_remove_' . $tour_id,
                    )) ?>
                    </td>
                    </tr>
                <? endforeach ?>
                </tbody>
                <tfoot>
                <tr><td colspan="7">
                <?=Button::createAccept(_('Speichern'), 'save_tour_settings') ?>
                </td></tr></tfoot>
            </table>
        <? else : ?>
            <?=_('Keine Touren vorhanden.')?>
        <? endif ?>
        </form><br><br></p></td>
    </tr>
</table>
</div>
<?
$sidebar = Sidebar::get();
$widget = new ViewsWidget();
$widget->addLink(_('Übersicht'), URLHelper::getURL('dispatch.php/tour/admin_overview'))->setActive(true);
$widget->addLink(_('Konflikte'), URLHelper::getURL('dispatch.php/tour/admin_conflicts'));
$sidebar->addWidget($widget);
$widget = new ActionsWidget();
$widget->addLink(_('Tour erstellen'), URLHelper::getLink('dispatch.php/tour/admin_details'), 'icons/16/blue/add.png', array());
$sidebar->addWidget($widget);
$search = new SearchWidget('?apply_tour_filter=1');
$search->addNeedle(_('Suchbegriff'), 'tour_searchterm');
$sidebar->addWidget($search);