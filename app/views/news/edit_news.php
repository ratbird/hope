<? use Studip\Button, Studip\LinkButton ?>
<? if(!empty($flash['question_text'])) : ?>
    <? $form_content = array('news_isvisible' => htmlspecialchars(serialize($news_isvisible)),
              'news_selectable_areas' => htmlspecialchars(serialize($area_options_selectable)), 
              'news_selected_areas' => htmlspecialchars(serialize($area_options_selected)), 
              'news_basic_js' => '',
              'news_comments_js' => '',
              'news_areas_js' => '',
              'news_allow_comments' => $news['allow_comments'],
              'news_topic' => $news['topic'],
              'news_body' => $news['body'],
              'news_date' => $news['date'],
              'news_expire' => $news['expire'],
              'news_allow_comments' => $news['allow_comments']) ?>
    <?=createQuestion2($flash['question_text'],
        array_merge($flash['question_param'], $form_content),
        $form_content,
        URLHelper::getURL('dispatch.php/'.$route.'#anker')); ?>
<? endif ?>
<form action="<?=URLHelper::getURL('dispatch.php/'.$route.'#anker')?>" method="POST" rel="<?=Request::isXhr() ? 'update_dialog' : ''?>">
<?=CSRFProtection::tokenTag(); ?>
<input type="hidden" name="news_basic_js" value=""> 
<input type="hidden" name="news_comments_js" value=""> 
<input type="hidden" name="news_areas_js" value=""> 
<input type="hidden" name="news_isvisible" value="<?=htmlspecialchars(serialize($news_isvisible))?>"> 
<input type="hidden" name="news_selectable_areas" value="<?=htmlReady(serialize($area_options_selectable));?>"> 
<input type="hidden" name="news_selected_areas" value="<?=htmlspecialchars(serialize($area_options_selected))?>"> 
<div id="news_dialog_content" style="overflow-y: auto; overflow-x: hidden; padding-right: 15px; padding-top: 10px">
<? if (count($_SESSION['messages'])) : ?>
    <? $anker = ''; ?>
<? endif ?>
<? if (Request::isXhr()) : ?>
    <? foreach (PageLayout::getMessages() as $msg) : ?>
        <?=$msg?>
    <? endforeach ?>
<? endif ?>
    <div id="news_basic">
        <table class="default collapsable news_category_header" style="padding: 0px; margin: 0px">
            <thead><tr>
                <th width="26">
                    <input name="toggle_news_basic" type="image" aria-label="<?= _('Formular f�r Grunddaten der Ank�ndigung einblenden oder ausblenden') ?>"
                        src="<?=$news_isvisible['news_basic'] ? Assets::image_path('icons/16/blue/arr_1down.png') : Assets::image_path('icons/16/blue/arr_1right.png')?>" <?= tooltip(_('Grunddaten ein-/ausblenden')) ?>>
                </th>
                <th><?=_("Grunddaten")?></th>
            </tr></thead>
        </table>
        <div id="news_basic_content" style="<?=$news_isvisible['news_basic'] ? '' : 'display: none'?>">
            <table class="default collapsable">
            <tbody>
                <tr>
                    <td colspan="2">
                        <label><?= _("Titel") ?><br>
                        <input type="text" name="news_topic" aria-label="<?= _('Titel der Ank�ndigung') ?>" 
                               value="<?= htmlReady($news['topic']) ?>" style="width: 90%;"></label>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <label><?= _("Inhalt") ?><br>
                        <? list ($body, $admin_msg) = explode("<admin_msg>", $news['body']); ?>
                        <textarea class="add_toolbar" name="news_body" style="resize: vertical; width: 90%" rows="6" 
                            wrap="virtual" placeholder="<?= _('Geben Sie hier den Ank�ndigungstext ein') ?>" 
                            aria-label="<?= _('Inhalt der Ank�ndigung') ?>"><?= htmlReady($body) ?></textarea></label>
                    </td>
                </tr>
                <tr>
                    <td>
                        <label><?=_("Einstelldatum")?><br>
                        <input type="text" class="news_date" name="news_startdate" value="<?=($news['date']) ? strftime('%x', $news['date']) : ""?>" aria-label="<?= _('Einstelldatum') ?>"></label>
                    </td>
                    <td>
                        <label><?=_("Ablaufdatum")?><br>
                        <input type="text" class="news_date" name="news_enddate" value="<?=($news['expire']) ? strftime('%x', $news['date']+$news['expire']) : ""?>" aria-label="<?= _('Ablaufdatum') ?>"></label>
                    </td>
                </tr>
            </tbody>
            </table>
        </div>
    </div>
    <br>
    <div id="news_comments">
        <? if ($anker == 'news_comments') : ?>
            <a name='anker'></a>
        <? endif ?>
        <table class="default collapsable news_category_header" style="padding: 0px; margin: 0px">
        <thead>
            <tr>
                <th width="26">
                    <input name="toggle_news_comments" type="image" aria-label="<?= _('Formular f�r Kommentarverwaltung der Ank�ndigung einblenden oder ausblenden') ?>"
                        src="<?=$news_isvisible['news_comments'] ? Assets::image_path('icons/16/blue/arr_1down.png') : Assets::image_path('icons/16/blue/arr_1right.png')?>" <?= tooltip(_('Kommentare ein-/ausblenden')) ?>>
                </th>
                <th>
                <? if ($news['allow_comments']) : ?>        
                    <?=_("Kommentare zu dieser Ank�ndigung (zugelassen)")?>
                <? else : ?>
                    <?=_("Kommentare zu dieser Ank�ndigung (gesperrt)")?>
                <? endif ?>
                </th>
            </tr>
        </thead>
        </table>
        <div id="news_comments_content" style="<?=$news_isvisible['news_comments'] ? '' : 'display: none'?>">
            <table class="default collapsable">
            <tbody>
                <tr>
                    <td width="26"></td>
                    <td colspan="2">
                    <? if ($news['allow_comments']): ?>
                        <input type="hidden" name="news_allow_comments" value="1"> 
                        <?=Button::create(_('Kommentare sperren'), 'comments_status_deny', array('style' => 'vertical-align:middle;')) ?>
                    <? else : ?>
                        <?=Button::create(_('Kommentare zulassen'), 'comments_status_allow', array('style' => 'vertical-align:middle;')) ?>
                    <? endif ?>
                    </td>
                </tr>
                <? if (is_array($comments) AND count($comments)) : ?>
                    <? foreach ($comments as $index => $comment): ?>
                        <?= $this->render_partial('../../templates/news/comment-box', compact('index', 'comment')) ?>
                    <? endforeach; ?>
                    <? if ($comments_admin): ?>
                        <tfoot><tr><td colspan="3" align="right" style="vertical-align:middle;">
                        <?=Button::create(_('Markierte Kommentare l�schen'), 'delete_marked_comments', array('style' => 'vertical-align:middle;', 'title' => _('Markierte Kommentare l�schen'))) ?>
                        </td></tr></tfoot>
                    <? endif ?>
                <? else : ?>
                    <tr>
                        <td width="26"></td>
                        <td colspan="2">
                            <?= _('Zu dieser Ank�ndigung sind keine Kommentare vorhanden.') ?>
                        </td>
                    </tr>
                <? endif ?>
            </tbody>
            </table>
        </div>
    </div>
    <br>
    <div id="news_areas">
        <? if ($anker == 'news_areas') : ?>
            <a name='anker'></a>
        <? endif ?>
        <table class="default collapsable news_category_header" style="padding: 0px; margin: 0px">
        <thead>
            <tr>
                <th width="26">
                    <input name="toggle_news_areas" type="image" aria-label="<?= _('Formular f�r Bereichszuordnungen der Ank�ndigung einblenden oder ausblenden') ?>" 
                        src="<?=$news_isvisible['news_areas'] ? Assets::image_path('icons/16/blue/arr_1down.png') : Assets::image_path('icons/16/blue/arr_1right.png')?>" <?= tooltip(_('Bereiche ein-/ausblenden')) ?>>
                </th>
                <th colspan="2"><?=_('In weiteren Bereichen anzeigen')?></th>
            </tr>
        </thead>
        </table>
        <div id="news_areas_content" style="<?=$news_isvisible['news_areas'] ? '' : 'display: none'?>">
            <table class="default collapsable">
            <tbody>
                <tr>
                    <td colspan="3">
                        <select name="search_preset" aria-label="<?= _('Vorauswahl bestimmter Bereiche, alternativ zur Suche') ?>" 
                                onchange="jQuery('input[name=area_search_preset]').click()" style="width: 45%">
                        <option><?=_('--- Suchvorlagen ---')?></option>
                        <? foreach($search_presets as $value => $title) : ?>
                            <option value="<?=$value?>">
                                <?=htmlReady($title)?>
                            </option>
                        <? endforeach ?>
                        </select>                
                        <input type="image" name="area_search_preset" src="<?= Assets::image_path('icons/16/blue/accept.png')?>" aria-label="<?= _('Vorauswahl anwenden') ?>">
                    </td>
                </tr>
                <tr>
                    <td colspan="3">
                        <label>
                        <input name="area_search_term" type="text" placeholder="<?=_('Suchen')?>" 
                               aria-label="<?= _('Suchbegriff') ?>" style="width: 45%"> 
                        <input type="image" name="area_search" src="<?= Assets::image_path('icons/16/blue/search.png')?>" 
                               aria-label="<?= _('Suche starten') ?>">
                        </label>
                    </td>
                </tr>
                <tr style="vertical-align: bottom">
                    <td colspan="3" style="vertical-align: bottom">
                        <div style="display: inline-block; float: left; width: 45%; height: 100%">
                            <label><?=_('Suchergebnis')?><br>
                            <select name="area_options_selectable[]" style="minWidth: 200px; width: 100%" size="7" multiple 
                                    aria-label="<?= _('Gefundene Bereiche, die der Ank�ndigung hinzugef�gt werden k�nnen') ?>"
                                    ondblclick="jQuery('input[name=news_add_areas]').click()">
                            <? foreach ($area_structure as $area_key => $area_data) : ?>
                                <? if (count($area_options_selectable[$area_key])) : ?>
                                    <option disabled style="padding-left: 26px; height: 16px; font-weight: bold; color: #ffffff; 
                                            background-image: url('<?=Assets::image_path('icons/16/white/'.$area_data['icon'])?>'); 
                                            background-repeat: no-repeat; background-color: #d1d1d1">
                                        <?=htmlReady($area_data['title'])?>
                                    </option>
                                    <? foreach ($area_options_selectable[$area_key] as $area_option_key => $area_option_title) : ?>
                                        <option <?= StudipNews::haveRangePermission('edit', $area_option_key) ? 'value="'.$area_option_key.'"' : 'disabled'?>
                                                <?=tooltip($area_option_title);?>>
                                        <?= htmlReady(mila($area_option_title))?>
                                    </option>
                                    <? endforeach ?>
                                <? endif ?>
                            <? endforeach ?>
                            <? if (!count($area_options_selectable)) : ?>
                                <option disabled></option>
                            <? endif ?>
                            </select>
                            </label>
                        </div>
                        <div style="display: inline-block; width: 10%; text-align: center">
                            <br>
                            <br>
                            <br>
                            <input type="image" name="news_add_areas" src="<?= Assets::image_path('icons/16/blue/arr_2right.png')?>" aria-label="<?= _('In den Suchergebnissen markierte Bereiche der Ank�ndigung hinzuf�gen') ?>">
                            <br><br>
                            <input type="image" name="news_remove_areas" src="<?= Assets::image_path('icons/16/blue/arr_2left.png')?>" aria-label="<?= _('Bei den bereits ausgew�hlten Bereichen die markierten Bereiche entfernen') ?>">
                        </div>
                        <div style="display: inline-block; float: right; width: 45%">
                            <? foreach ($area_structure as $area_key => $area_data) : 
                                $area_count += (int) count($area_options_selected[$area_key]);
                            endforeach ?>
                            <label>
                            <div id="news_area_text">
                                <? if ($area_count == 0) : ?>
                                    <?=_('Keine Bereiche ausgew�hlt')?>
                                <? elseif ($area_count == 1) : ?>
                                    <?=_('1 Bereich ausgew�hlt')?>
                                <? else : ?>
                                    <?=sprintf(_('%s Bereiche ausgew�hlt'), $area_count)?>
                                <? endif ?>
                            </div>
                            <select name="area_options_selected[]" style="minWidth: 200px; width: 100%" size="7" multiple 
                                    aria-label="<?= _('Bereiche, in denen die Ank�ndigung angezeigt wird') ?>"
                                    ondblclick="jQuery('input[name=news_remove_areas]').click()">
                            <? foreach ($area_structure as $area_key => $area_data) : ?>
                                <? if (count($area_options_selected[$area_key])) : ?>
                                    <option disabled style="padding-left: 26px; height: 16px; font-weight: bold; color: #ffffff; 
                                            background-image: url('<?=Assets::image_path('icons/16/white/'.$area_data['icon'])?>'); 
                                            background-repeat: no-repeat; background-color: #d1d1d1">
                                        <?=htmlReady($area_data['title'])?>
                                    </option>
                                    <? foreach ($area_options_selected[$area_key] as $area_option_key => $area_option_title) : ?>
                                        <option <?= (StudipNews::haveRangePermission('edit', $area_option_key) OR $may_delete) ? 'value="'.$area_option_key.'"' : 'disabled'?> 
                                                <?=tooltip($area_option_title);?>>
                                            <?= htmlReady(mila($area_option_title))?>
                                        </option>
                                    <? endforeach ?>
                                <? endif ?>
                            <? endforeach ?>
                            <? if (!count($area_options_selected)) : ?>
                                <option disabled></option>
                            <? endif ?>
                            </select>
                            </label>
                        </div>
                    </td>
                </tr>
            </tbody>
            </table>
        </div>
    </div>
    <br>
</div>
<div style="margin-right: 15px; border-top: 1px solid #d1d1d1">
<?  if ($news["mkdate"]) : ?>    
    <?= Button::createAccept(_('�nderungen speichern'), 'save_news') ?>
<? else : ?>
    <?= Button::createAccept(_('Ank�ndigung erstellen'), 'save_news') ?>
<? endif ?>
<? if (Request::isXhr()) : ?>
    <?= LinkButton::createCancel(_('Schlie�en'), URLHelper::getURL(''), array('rel' => 'close_dialog')) ?>
<? endif ?>
</div>
</form>
<script>
    jQuery('.news_date').datepicker();
</script>