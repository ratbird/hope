<? use Studip\Button, Studip\LinkButton; ?>
<? if(!empty($flash['question_text'])) : ?>
    <?= createQuestion2($flash['question_text'],
        array_merge($flash['question_param'], 
        array('news_filter_term' => htmlReady($news_searchterm),
              'news_filter_start' => $news_startdate,
              'news_filter_end' => $news_enddate,
              'news_filter' => 'set')),
        array('news_filter_term' => htmlReady($news_searchterm),
              'news_filter_start' => $news_startdate,
              'news_filter_end' => $news_enddate,
              'news_filter' => 'set'),
        $controller->url_for('news/admin_news/'.$area_type)); ?>
<? endif ?>
<div class="news_admin">
<h2><?= _('Meine Ankündigungen') ?></h2>
<p class="info">
        <form action="<?=$controller->url_for('news/admin_news/'.$area_type)?>" id="admin_news_form" method="POST">
        <input type="hidden" name="news_filter" value="set">
        <input type="hidden" name="news_filter_term" value="<?=htmlReady($news_searchterm)?>">
        <input type="hidden" name="news_filter_start" value="<?=$news_startdate?>">
        <input type="hidden" name="news_filter_end" value="<?=$news_enddate?>">
        <?=CSRFProtection::tokenTag(); ?>
        <table class="default">
        <thead>
        <tr><th colspan="2">
        <label><?= _("Suchbegriff:") ?>
        <input type="text" name="news_searchterm" aria-label="<?= _('Suchbegriff') ?>" value="<?= htmlReady($news_searchterm)?>"></label>
        &nbsp;&nbsp;
        <label><?= _("Anzeige von:") ?>
        <input class="news_date" type="text" size="12" name="news_startdate" aria-label="<?= _('Ankündigungen anzeigen, die ab diesem Datum sichtbar sind') ?>" value="<?= ($news_startdate) ? date('d.m.Y', $news_startdate) : '' ?>"></label>
        &nbsp;&nbsp;
        <label><?= _("bis:") ?>
        <input class="news_date" type="text" size="12" name="news_enddate" aria-label="<?= _('Ankündigungen anzeigen, die vor diesem Datum sichtbar sind') ?>" value="<?= ($news_enddate) ? date('d.m.Y', $news_enddate) : '' ?>"></label>
        &nbsp;&nbsp;
        <?=Button::create(_('Filter anwenden'), 'apply_news_filter', array('aria-label' => _('Liste mit Suchbegriff und/oder Zeitraum filtern')))?>
        </th></tr></thead>
        <? if ($filter_text) : ?>
            <tfoot><tr><td colspan="1">
            <?=htmlReady($filter_text)?>
            </td><td><div class="news_reset_filter">
            <?=Button::create(_('Auswahl aufheben'), 'reset_filter')?>
            </div>
            </td></tr></tfoot>
        <? endif ?>
        </table>
        <? if (count($news_items)) : ?>
            <? foreach ($area_structure as $type => $area_data) : ?>
                <? $last_title = 'none' ?>
                <? if (count($news_items[$type])) : ?>
                    <br>
                    <br>
                    <table class="default">
                    <? if (!$area_type) : ?>
                        <caption>
                            <img src="<?=Assets::image_path('icons/32/grey/'.$area_data['icon'])?>" class="news_area_icon">&nbsp;
                            <div class="news_area_title"><?=htmlReady($area_data['title'])?></div>
                        </caption>
                    <? endif ?>
                    <colgroup>
                        <col width="20">
                        <col>
                        <col width="25%">
                        <col width="15%">
                        <col width="15%">
                        <col width="80">
                    </colgroup>                   
                    <thead><tr>
                        <th></th>
                        <th><?=_("Überschrift")?></th>
                        <th><?=_("Autor")?></th>
                        <th><?=_("Einstelldatum")?></th>
                        <th><?=_("Ablaufdatum")?></th>
                        <th><?=_("Aktion")?></th>
                    </tr></thead>
                    <tbody>
                    <? foreach ($news_items[$type] as $news) : ?>
                        <? $title = $news['title'] ?> 
                        <? if ($title != $last_title) : ?>
                            <? if ($last_title != 'none') : ?>
                            <? endif ?>
                            <? if ($title) : ?>
                                <tr><th colspan="6"><?=mila(htmlReady($news['title'])) . ' ' . htmlReady($news['semester'])?></th></tr>
                            <? endif ?>
                            <? $last_title = $title ?>
                        <? endif ?>
                        <tr>
                        <td><input type="CHECKBOX" name="mark_news[]" value="<?=$news['object']->news_id.'_'.$news['range_id']?>" aria-label="<?= _('Diese Ankündigung zum Entfernen vormerken')?>" <?=tooltip(_("Diese Ankündigung zum Entfernen vormerken"),false)?>></td>
                        <td><?=htmlReady($news['object']->topic)?></td>
                        <? list ($body, $admin_msg) = explode("<admin_msg>", $news['object']->body); ?>
                        <td><?=htmlReady($news['object']->author)?></td>
                        <td><?=strftime("%d.%m.%y", $news['object']->date)?></td>
                        <td><?=strftime("%d.%m.%y", $news['object']->date + $news['object']->expire)?></td>
                        <td>
                        <a href="<?=URLHelper::getURL('dispatch.php/news/edit_news/'.$news['object']->news_id)?>" rel="get_dialog" target="_blank" <?=tooltip(_('Ankündigung bearbeiten'))?>>
                        <img src="<?= Assets::image_path('icons/16/blue/edit.png')?>"></a>
                        <a href="<?=URLHelper::getURL('dispatch.php/news/edit_news/new/template/'.$news['object']->news_id)?>" rel="get_dialog" target="_blank" aria-label="<?= _('Kopieren, um neue Ankündigung zu erstellen')?>" <?=tooltip(_('Kopieren, um neue Ankündigung zu erstellen'))?>>
                        <img src="<?= Assets::image_path('icons/16/blue/export/news.png')?>"></a>
                        <? if ($news['object']->havePermission('unassign', $news['range_id'])) : ?>
                            <input type="image" name="news_remove_<?=$news['object']->news_id?>_<?=$news['range_id']?>" src="<?= Assets::image_path('icons/16/blue/remove.png')?>" aria-label="<?= _('Ankündigung löschen')?>" <?=tooltip(_("Ankündigung aus diesem Bereich entfernen"),false)?>>
                        <? else : ?>
                            <input type="image" name="news_remove_<?=$news['object']->news_id?>_<?=$news['range_id']?>" src="<?= Assets::image_path('icons/16/blue/trash.png')?>" aria-label="<?= _('Ankündigung löschen')?>" <?=tooltip(_("Ankündigung löschen"),false)?>>
                        <? endif ?>
                        </td>
                        </tr>
                    <? endforeach ?>
                    </tbody>
                    </table>
                <? endif ?>
            <? endforeach ?>
            <br>
            <br>
            <table class="default">
            <tfoot>
            <tr><td colspan="6">
            <?=Button::create(_('Alle markierten Ankündigungen entfernen'), 'remove_marked_news') ?>
            </td></tr></tfoot></table>
        <? else : ?>
            <?=_('Keine Ankündigungen vorhanden.')?>
        <? endif ?>
        </form><br><br>
</p>
</div>
<script>
    jQuery('.news_date').datepicker();
</script>