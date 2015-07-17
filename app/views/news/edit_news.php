<? use Studip\Button, Studip\LinkButton ?>
<? if(!empty($flash['question_text'])) : ?>
    <? $form_content = array('news_isvisible' => htmlReady(serialize($news_isvisible)),
              'news_selectable_areas' => htmlReady(serialize($area_options_selectable)),
              'news_selected_areas' => htmlReady(serialize($area_options_selected)),
              'news_basic_js' => '',
              'news_comments_js' => '',
              'news_areas_js' => '',
              'news_allow_comments' => $news['allow_comments'],
              'news_topic' => $news['topic'],
              'news_body' => $news['body'],
              'news_startdate' => ($news['date']) ? date('d.m.Y', $news['date']) : "",
              'news_enddate' => ($news['expire']) ? date('d.m.Y', $news['date']+$news['expire']) : "",
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
<input type="hidden" name="news_isvisible" value="<?=htmlReady(serialize($news_isvisible))?>">
<input type="hidden" name="news_selectable_areas" value="<?=htmlReady(serialize($area_options_selectable));?>">
<input type="hidden" name="news_selected_areas" value="<?=htmlReady(serialize($area_options_selected))?>">
<div id="news_dialog_content" class="news_dialog_content">
<? if (count($_SESSION['messages'])) : ?>
    <? $anker = ''; ?>
<? endif ?>
<? if (Request::isXhr()) : ?>
    <? foreach (PageLayout::getMessages() as $msg) : ?>
        <?=$msg?>
    <? endforeach ?>
<? endif ?>
    <div id="news_basic">
        <table class="default nohover news_category_header">
            <thead><tr>
                <th width="26">
                    <?= Assets::input('icons/16/blue/' . ($news_isvisible['news_basic'] ? 'arr_1down' : 'arr_1right') . '.png',
                                      tooltip2(_('Formular für Grunddaten der Ankündigung einblenden oder ausblenden')) + array(
                                          'name' => 'toggle_news_basic',
                    )) ?>
                </th>
                <th><?=_("Grunddaten")?></th>
            </tr></thead>
        </table>
        <div id="news_basic_content" style="<?=$news_isvisible['news_basic'] ? '' : 'display: none'?>">
            <table class="default nohover">
            <tbody>
                <tr>
                    <td colspan="2">
                        <label><?= _("Titel") ?><br>
                        <input type="text" name="news_topic" class="news_topic news_prevent_submit" aria-label="<?= _('Titel der Ankündigung') ?>"
                               value="<?= htmlReady($news['topic']) ?>"></label>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <label><?= _("Inhalt") ?><br>
                        <? list ($body, $admin_msg) = explode("<admin_msg>", $news['body']); ?>
                        <textarea class="news_body add_toolbar" name="news_body" rows="6"
                            wrap="virtual" placeholder="<?= _('Geben Sie hier den Ankündigungstext ein') ?>"
                            aria-label="<?= _('Inhalt der Ankündigung') ?>"><?= htmlReady($body) ?></textarea></label>
                    </td>
                </tr>
                <tr>
                    <td>
                        <label><?=_("Veröffentlichungsdatum")?><br>
                        <input type="text" class="news_date news_prevent_submit" name="news_startdate" value="<?=($news['date']) ? date('d.m.Y', $news['date']) : ""?>" aria-label="<?= _('Einstelldatum') ?>"></label>
                    </td>
                    <td>
                        <label><?=_("Ablaufdatum")?><br>
                        <input type="text" class="news_date news_prevent_submit" name="news_enddate" value="<?=($news['expire']) ? date('d.m.Y', $news['date']+$news['expire']) : ""?>" aria-label="<?= _('Ablaufdatum') ?>"></label>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                    <? if ($anker == 'news_comments') : ?>
                        <a name='anker'></a>
                    <? endif ?>
                    <? if ($news['allow_comments']): ?>
                        <input type="hidden" name="news_allow_comments" value="1">
                        <?= Assets::input('icons/16/blue/checkbox-checkbox.png', tooltip2(_('Kommentare sperren')) + array(
                                'name' => 'comments_status_deny',
                        )) ?>
                        <?= _('Kommentare zulassen') ?>
                    <? else : ?>
                        <?= Assets::input('icons/16/blue/checkbox-unchecked.png', tooltip2(_('Kommentare zulassen')) + array(
                                'name' => 'comments_status_allow',
                        )) ?>
                        <?= _('Kommentare zulassen') ?>
                    <? endif ?>
                    </td>
                </tr>
            </tbody>
            </table>
        </div>
    </div>
    <br>
    <? if (count($comments)) : ?>
    <div id="news_comments">
        <table class="default nohover news_category_header">
        <thead>
            <tr>
                <th width="26">
                    <?= Assets::input('icons/16/blue/' . ($news_isvisible['news_comments'] ? 'arr_1down' : 'arr_1right') . '.png',
                                      tooltip2(_('Formular für Kommentarverwaltung der Ankündigung einblenden oder ausblenden')) + array(
                                          'name' => 'toggle_news_comments',
                    ))?>
                </th>
                <th>
                <? if ($news['allow_comments']) : ?>
                    <?=_("Kommentare zu dieser Ankündigung")?>
                <? else : ?>
                    <?=_("Kommentare zu dieser Ankündigung")?>
                <? endif ?>
                </th>
            </tr>
        </thead>
        </table>
        <div id="news_comments_content" style="<?=$news_isvisible['news_comments'] ? '' : 'display: none'?>">
            <table class="default nohover">
            <tbody>
                <? if (is_array($comments) AND count($comments)) : ?>
                    <? foreach ($comments as $index => $comment): ?>
                        <?= $this->render_partial('../../templates/news/comment-box', compact('index', 'comment')) ?>
                    <? endforeach; ?>
                    <? if ($comments_admin): ?>
                        <tfoot><tr><td colspan="3" align="right">
                        <?=Button::create(_('Markierte Kommentare löschen'), 'delete_marked_comments', array('title' => _('Markierte Kommentare löschen'))) ?>
                        </td></tr></tfoot>
                    <? endif ?>
                <? else : ?>
                    <tr>
                        <td width="26"></td>
                        <td colspan="2">
                            <?= _('Zu dieser Ankündigung sind keine Kommentare vorhanden.') ?>
                        </td>
                    </tr>
                <? endif ?>
            </tbody>
            </table>
        </div>
    </div>
    <br>
    <? endif ?>
    <div id="news_areas">
        <? if ($anker == 'news_areas') : ?>
            <a name='anker'></a>
        <? endif ?>
        <table class="default news_category_header">
        <thead>
            <tr>
                <th width="26">
                    <?= Assets::input('icons/16/blue/' . ($news_isvisible['news_areas'] ? 'arr_1down' : 'arr_1right') . '.png',
                                      tooltip2(_('Formular für Bereichszuordnungen der Ankündigung einblenden oder ausblenden')) + array(
                                          'name' => 'toggle_news_areas',
                    )) ?>
                </th>
                <th colspan="2"><?=_('In weiteren Bereichen anzeigen')?></th>
            </tr>
        </thead>
        </table>
        <div id="news_areas_content" style="<?=$news_isvisible['news_areas'] ? '' : 'display: none'?>">
            <table class="default nohover">
            <tbody>
                <tr>
                    <td colspan="3">
                        <select name="search_preset" aria-label="<?= _('Vorauswahl bestimmter Bereiche, alternativ zur Suche') ?>"
                                onchange="jQuery('input[name=area_search_preset]').click()">
                        <option><?=_('--- Suchvorlagen ---')?></option>
                        <? foreach($search_presets as $value => $title) : ?>
                            <option value="<?=$value?>"<?=($this->current_search_preset == $value) ? ' selected' : '' ?>>
                                <?=htmlReady($title)?>
                            </option>
                        <? endforeach ?>
                        </select>
                        <?= Assets::input('icons/16/blue/accept.png', tooltip2(_('Vorauswahl anwenden')) + array(
                                'name' => 'area_search_preset',
                        )) ?>
                    </td>
                </tr>
                <tr>
                    <td colspan="3">
                        <label>
                        <input name="area_search_term" class="news_search_term" type="text" placeholder="<?=_('Suchen')?>"
                               aria-label="<?= _('Suchbegriff') ?>">
                        <?= Assets::input('icons/16/blue/search.png', tooltip2(_('Suche starten')) + array(
                                'name' => 'area_search',
                        )) ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <td colspan="3">
                        <div class="news_area_selectable">
                            <label><?=_('Suchergebnis')?><br>
                            <select name="area_options_selectable[]" class="news_area_options" size="7" multiple
                                    aria-label="<?= _('Gefundene Bereiche, die der Ankündigung hinzugefügt werden können') ?>"
                                    ondblclick="jQuery('input[name=news_add_areas]').click()">
                            <? foreach ($area_structure as $area_key => $area_data) : ?>
                                <? if (count($area_options_selectable[$area_key])) : ?>
                                    <option disabled class="news_area_title"
                                            style="background-image: url('<?=Assets::image_path('icons/16/white/'.$area_data['icon'])?>');">
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
                            </select>
                            </label>
                        </div>
                        <div class="news_area_actions">
                            <br>
                            <br>
                            <br>
                            <?= Assets::input('icons/16/blue/arr_2right.png', tooltip2(_('In den Suchergebnissen markierte Bereiche der Ankündigung hinzufügen')) + array(
                                    'name' => 'news_add_areas',
                            )) ?>
                            <br><br>
                            <?= Assets::input('icons/16/blue/arr_2left.png', tooltip2(_('Bei den bereits ausgewählten Bereichen die markierten Bereiche entfernen')) + array(
                                    'name' => 'news_remove_areas',
                            )) ?>
                        </div>
                        <div class="news_area_selected">
                            <? foreach ($area_structure as $area_key => $area_data) :
                                $area_count += (int) count($area_options_selected[$area_key]);
                            endforeach ?>
                            <label>
                            <div id="news_area_text">
                                <? if ($area_count == 0) : ?>
                                    <?=_('Keine Bereiche ausgewählt')?>
                                <? elseif ($area_count == 1) : ?>
                                    <?=_('1 Bereich ausgewählt')?>
                                <? else : ?>
                                    <?=sprintf(_('%s Bereiche ausgewählt'), $area_count)?>
                                <? endif ?>
                            </div>
                            <select name="area_options_selected[]" class="news_area_options" size="7" multiple
                                    aria-label="<?= _('Bereiche, in denen die Ankündigung angezeigt wird') ?>"
                                    ondblclick="jQuery('input[name=news_remove_areas]').click()">
                            <? foreach ($area_structure as $area_key => $area_data) : ?>
                                <? if (count($area_options_selected[$area_key])) : ?>
                                    <option disabled  class="news_area_title"
                                            style="background-image: url('<?=Assets::image_path('icons/16/black/'.$area_data['icon'])?>');">
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
<div class="news_dialog_buttons">
<?  if ($news["mkdate"]) : ?>
    <?= Button::createAccept(_('Änderungen speichern'), 'save_news') ?>
<? else : ?>
    <?= Button::createAccept(_('Ankündigung erstellen'), 'save_news') ?>
<? endif ?>
<? if (Request::isXhr()) : ?>
    <?= LinkButton::createCancel(_('Schließen'), URLHelper::getURL(''), array('rel' => 'close_dialog')) ?>
<? endif ?>
</div>
</form>
<script>
    jQuery('.news_date').datepicker();
    jQuery('.news_prevent_submit').keydown(function(event) {
        if (event.which === 13) {
            event.preventDefault();
        }
    });
    jQuery('input[name=area_search_term]').keydown(function(event) {
        if (event.which === 13) {
            jQuery('input[name=area_search]').click();
            event.preventDefault();
        }
    });
</script>