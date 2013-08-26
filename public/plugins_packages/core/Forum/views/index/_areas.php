<? if (empty($list)) return; ?>
<br>
<div id="sortable_areas">
<? foreach ($list as $category_id => $entries) : ?>
<table class="forum <?= $has_perms && $category_id != $seminar_id ? 'movable' : '' ?>" data-category-id="<?= $category_id ?>">
    <thead id="tutorCategory">
    <tr>
        <td class="forum_header <?= ForumPerm::has('sort_category', $seminar_id) && $category_id != $seminar_id ? 'handle' : '' ?>" colspan="2">
            <a name="cat_<?= $category_id ?>"></a>
            <span class="corners-top"></span>
            <span class="heading">
                <span class="category_name" <?= Request::get('edit_category') != $category_id ? '' : 'style="display: none;"' ?>>
                    <?= htmlReady($categories[$category_id]) ?>
                </span>
            </span>
            <span class="heading_edit" style="<?= Request::get('edit_category') == $category_id ? '' : 'display: none;' ?> margin-left: 5px;">
                <form method="post" action="<?= PluginEngine::getLink('coreforum/index/edit_category/' . $category_id) ?>">
                    <input type="text" required name="name" size="40" value="<?= htmlReady($categories[$category_id]) ?>">

                    <?= Studip\Button::createAccept('Kategorie speichern', '', 
                        array('onClick' => "javascript:STUDIP.Forum.saveCategoryName('". $category_id ."'); return false;")) ?>
                    <?= Studip\LinkButton::createCancel('Abbrechen', PluginEngine::getLink('coreforum/index/index#cat_'. $category_id),
                        array('onClick' => "STUDIP.Forum.cancelEditCategoryName('". $category_id ."'); return false;")) ?>
                </form>
            </span>
        </td>

        <td class="forum_header" data-type="answers">
            <span class="no-corner"></span>
            <span class="heading"><?= _("Beiträge") ?></span>
        </td>

        <td class="forum_header" colspan="2" data-type="last_posting">
            <span class="corners-top-right"></span>
            <span class="heading" style="float: left"><?= _("letzte Antwort") ?></span>
            <? if (ForumPerm::has('edit_category', $seminar_id) || ForumPerm::has('remove_category', $seminar_id)) : ?>
            <span style="float: right; padding-right: 5px;" id="tutorCategoryIcons">
                <? if ($category_id == $seminar_id) : ?>
                <?= tooltipIcon(_('Diese vordefinierte Kategorie kann nicht bearbeitet oder gelöscht werden.'
                        . 'Für Autor/innen taucht sie allerdings nur auf, wenn sie Bereiche enthält.')) ?>
                <? else : ?>
                    <? if (ForumPerm::has('edit_category', $seminar_id)) : ?>
                    <a href="<?= PluginEngine::getLink('coreforum/index/?edit_category=' . $category_id) ?>"
                        onClick="javascript:STUDIP.Forum.editCategoryName('<?= $category_id ?>'); return false;">
                        <?= Assets::img('icons/16/white/edit.png', array('title' => 'Name der Kategorie ändern')) ?>
                    </a>
                    <? endif ?>

                    <? if(ForumPerm::has('remove_category', $seminar_id)) : ?>
                    <a href="<?= PluginEngine::getLink('coreforum/index/remove_category/' . $category_id) ?>"
                        onClick="STUDIP.Forum.deleteCategory('<?= $category_id ?>'); return false;">
                        <?= Assets::img('icons/16/white/trash.png', array('title' => 'Kategorie entfernen')) ?>
                    </a>
                    <? endif ?>
                <? endif ?>
            </span>
            <? endif ?>
        </td>
    </tr>
    </thead>


    <tbody class="sortable">
    <!-- this row allows dropping on otherwise empty categories -->
    <tr class="sort-disabled">
        <td class="areaborder" style="height: 5px"colspan="7"> </td>
    </tr>

    <? if (!empty($entries)) foreach ($entries as $entry) :
        $jump_to_topic_id = $entry['topic_id']; ?>

    <tr id="tutorArea" data-area-id="<?= $entry['topic_id'] ?>" <?= ($has_perms) ? 'class="movable"' : '' ?>>
        <td class="areaentry icon">
            <? if (ForumPerm::has('sort_area', $seminar_id)) : ?>
            <img src="<?= $picturepath ?>/anfasser_48.png" class="handle" id="tutorMoveArea">
            <? endif ?>

            <a href="<?= PluginEngine::getLink('coreforum/index/index/'. $jump_to_topic_id .'#'. $jump_to_topic_id) ?>">
            <? if ($entry['chdate'] >= $visitdate && $entry['owner_id'] != $GLOBALS['user']->id): ?>
                <?= Assets::img('icons/16/red/new/forum.png', array(
                    'title' => _('Dieser Eintrag ist neu!'),
                    'id'    => 'tutorNotificationIcon',
                    'style' => 'margin-bottom: 15px;'
                )) ?>
            <? else : ?>
                <? $num_postings = ForumVisit::getCount($entry['topic_id'], $visitdate) ?>
                <? $text = ForumHelpers::getVisitText($num_postings, $entry['topic_id'], $constraint['depth']) ?>
                <? if ($num_postings > 0) : ?>
                    <?= Assets::img('icons/16/red/forum.png', array(
                        'title' => $text,
                        'id'    => 'tutorNotificationIcon',
                        'style' => 'margin-bottom: 15px;'
                    )) ?>
                <? else : ?>
                    <?= Assets::img('icons/16/black/forum.png', array(
                        'title' => $text,
                        'id'    => 'tutorNotificationIcon',
                        'style' => 'margin-bottom: 15px;'
                    )) ?>
                <? endif ?>
            <? endif ?>
            </a>
        </td>
        <td class="areaentry">
            <div style="position: relative;<?= Request::get('edit_area') == $entry['topic_id'] ? 'height: auto;' : '' ?>">
                
                <span class="areadata" <?= Request::get('edit_area') != $entry['topic_id'] ? '' : 'style="display: none;"' ?>>
                    <a href="<?= PluginEngine::getLink('coreforum/index/index/'. $jump_to_topic_id .'#'. $jump_to_topic_id) ?>">
                        <span class="areaname"><?= htmlReady($entry['name_raw']) ?></span>
                    </a>
                    <div class="areacontent">
                        <?= htmlReady(ForumEntry::killEdit(substr($entry['content_raw'], 0, 150))) ?>
                        <? if(strlen($entry['content_raw']) > 150) : ?>...<? endif ?>
                    </div>
                </span>
                

                <? if (ForumPerm::has('edit_area', $seminar_id)) : ?>
                <span class="areaname_edit" style="<?= Request::get('edit_area') == $entry['topic_id'] ? '' : 'display: none;' ?>text-align: center;">
                    <div style="width: 90%">
                        <form method="post" action="<?= PluginEngine::getLink('coreforum/index/edit_area/' . $entry['topic_id']) ?>">
                            <input type="text" name="name" size="20" style="width: 100%;" value="<?= $entry['name'] ?>" onClick="jQuery(this).focus()"><br>
                            <textarea name="content" style="height: 3em;" onClick="jQuery(this).focus()"><?= $entry['content_raw'] ?></textarea>

                            <span class="large_screen">
                                <?= Studip\Button::createAccept('Speichern', '',
                                    array('onClick' => "STUDIP.Forum.saveArea('". $entry['topic_id'] ."'); return false;")) ?>
                                <?= Studip\LinkButton::createCancel('Abbrechen', PluginEngine::getLink('coreforum/index'), 
                                    array('onClick' => "STUDIP.Forum.cancelEditArea('". $entry['topic_id'] ."'); return false;")) ?>
                            </span>
                            
                            <span class="small_screen">
                                <?= Assets::img('icons/16/green/accept.png', array(
                                    'title'   => _('Speichern'),
                                    'onClick' => "STUDIP.Forum.saveArea('". $entry['topic_id'] ."'); return false;"
                                )) ?>
                                <?= Assets::img('icons/16/red/decline.png', array(
                                    'title'   => _('Speichern'),
                                    'onClick' => "STUDIP.Forum.cancelEditArea('". $entry['topic_id'] ."'); return false;"
                                )) ?>
                            </span>
                        </form>
                    </div>
                </span>
                <? endif ?>
                
                <span class="action-icons" <? if(ForumPerm::has('edit_area', $seminar_id)) : ?> id="tutorAreaIcons"<? endif ?> <?= Request::get('edit_area') != $entry['topic_id'] ? '' : 'style="display: none;"' ?>>
                    <? if (ForumPerm::has('edit_area', $seminar_id)) : ?>
                    <a href="<?= PluginEngine::getLink('coreforum/index/?edit_area=' . $entry['topic_id']) ?>"
                        onClick="STUDIP.Forum.editArea('<?= $entry['topic_id'] ?>');return false;">
                        <?= Assets::img('icons/16/blue/edit.png',
                            array('class' => 'edit-area', 'title' => 'Name/Beschreibung des Bereichs ändern')) ?>
                    </a>
                    <? endif ?>
                    
                    <? if (ForumPerm::has('remove_area', $seminar_id)) : ?>
                    <a href="<?= PluginEngine::getLink('coreforum/index/delete_entry/' . $entry['topic_id']) ?>"
                       onClick="STUDIP.Forum.deleteArea(this, '<?= $entry['topic_id'] ?>'); return false;">
                        <?= Assets::img('icons/16/blue/trash.png',
                            array('class' => 'delete-area', 'title' => 'Bereich mitsamt allen Einträgen löschen!')) ?>
                    </a>
                    <? endif ?>
                </span>
            </div>
        </td>

        <td class="areaentry postings">
            <span id="tutorNumPostings">
                <?= ($entry['num_postings'] > 0) ? ($entry['num_postings'] - 1) : 0 ?>
            </span>
        </td>

        <td class="areaentry answer">
            <? if (is_array($entry['last_posting'])) : ?>
            <?= _("von") ?>
            <a href="<?= UrlHelper::getLink('about.php?username='. $entry['last_posting']['username']) ?>">
                    <?= htmlReady($entry['last_posting']['user_fullname']) ?>
            </a><br>
            <?= _("am") ?> <?= strftime($time_format_string_short, (int)$entry['last_posting']['date']) ?>
            <a href="<?= PluginEngine::getLink('coreforum/index/index/'. $entry['last_posting']['topic_id']) ?>#<?= $entry['last_posting']['topic_id'] ?>" alt="<?= $infotext ?>" title="<?= $infotext ?>">
                <?= Assets::img('icons/16/blue/link-intern.png', array('title' => $infotext = _("Direkt zum Beitrag..."), 'id' => 'tutorLatestAnswer')) ?>
            </a>
            <? else: ?>
            <br>
            <?= _('keine Antworten') ?>
            <? endif; ?>
        </td>
        
        <td class="areaentry icon" style="text-align: right; padding-right: 2px;">
            <? if (ForumPerm::has('sort_area', $seminar_id)) : ?>
            <img src="<?= $picturepath ?>/anfasser_48.png" class="handle" id="tutorMoveArea">
            <? endif ?>
        </td>

    </tr>
    <? endforeach; ?>
    </tbody>

    <tfoot>
    <? if ($category_id && ForumPerm::has('add_area', $seminar_id)) : ?>
    <? if (Request::get('add_area') != $category_id) : ?>
    <tr class="add_area">
        <td class="areaborder" colspan="5" class="add_area" onClick="STUDIP.Forum.addArea('<?= $category_id ?>'); return false;">
            <a href="<?= PluginEngine::getLink('coreforum/index/index/?add_area=' . $category_id)?>#cat_<?= $category_id ?>"  title="<?= _('Neuen Bereich zu dieser Kategorie hinzufügen.') ?>">
                <span><?= _('Bereich hinzufügen') ?></span>
                <?= Assets::img('icons/16/white/add.png', array('id' => 'tutorAddArea')) ?>
            </a>
        </td>
    </tr>
    <? endif ?>

    <tr <?= (Request::get('add_area') != $category_id) ? 'style="display: none"' : '' ?> class="new_area">
        <td class="areaentry"></td>
        <td class="areaentry">
            <form class="add_area_form" style="display: bgnone" method="post" action="<?= PluginEngine::getLink('coreforum/index/add_area/' . $category_id) ?>">
                <?= CSRFProtection::tokenTag() ?>
                <input type="text" name="name" size="50" style="width: 99%;" placeholder="<?= _('Name des neuen Bereiches') ?>" required><br>
                <textarea name="content" style="height: 3em; width: 99%;" placeholder="<?= _('Optionale Beschreibung des neuen Bereiches') ?>"></textarea>

                <?= Studip\Button::create('Bereich hinzufügen') ?>
                <?= Studip\LinkButton::createCancel('Abbrechen', PluginEngine::getLink('coreforum/index/index#cat_'. $category_id),
                        array('onClick' => "javascript:STUDIP.Forum.cancelAddArea(); return false;")) ?>
            </form>
        </td>
        <td class="areaentry postings">0</td>
        <td class="areaentry answer" colspan="2"><br><?= _('keine Antworten') ?></td>
    </tr>
    <? endif ?>


    <!-- bottom border -->
    <tr>
        <td class="areaborder" colspan="5">
            <span class="corners-bottom"><span></span></span>
        </td>
    </tr>
    <tr>
        <td colspan="6">&nbsp;</td>
    </tr>
    </tfoot>
</table>
<? endforeach ?>
</div>

<?= $this->render_partial('joyride/areas.php') ?>
