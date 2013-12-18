<? if (empty($list)) return; ?>
<br>
<div id="sortable_areas">
<? foreach ($list as $category_id => $entries) : ?>
<a name="cat_<?= $category_id ?>"></a>
<table class="default forum <?= ForumPerm::has('sort_category', $seminar_id) ? 'movable handle' : '' ?>" data-category-id="<?= $category_id ?>">
    <caption>
        <? if (ForumPerm::has('edit_category', $seminar_id) || ForumPerm::has('remove_category', $seminar_id)) : ?>
        <span class="actions" id="tutorCategoryIcons">
            <? if ($category_id == $seminar_id) : ?>
            <?= tooltipIcon(_('Diese vordefinierte Kategorie kann nicht bearbeitet oder gel�scht werden.'
                    . 'F�r Autor/innen taucht sie allerdings nur auf, wenn sie Bereiche enth�lt.')) ?>
            <? else : ?>
                <? if (ForumPerm::has('edit_category', $seminar_id)) : ?>
                <a href="<?= PluginEngine::getLink('coreforum/index/?edit_category=' . $category_id) ?>"
                    onClick="javascript:STUDIP.Forum.editCategoryName('<?= $category_id ?>'); return false;">
                    <?= Assets::img('icons/16/blue/edit.png', array('title' => 'Name der Kategorie �ndern')) ?>
                </a>
                <? endif ?>

                <? if(ForumPerm::has('remove_category', $seminar_id)) : ?>
                <a href="<?= PluginEngine::getLink('coreforum/index/remove_category/' . $category_id) ?>"
                    onClick="STUDIP.Forum.deleteCategory('<?= $category_id ?>'); return false;">
                    <?= Assets::img('icons/16/blue/trash.png', array('title' => 'Kategorie entfernen')) ?>
                </a>
                <? endif ?>
            <? endif ?>
        </span>
        <? endif ?>

        <span id="tutorCategory" class="category_name">
            <? if (Request::get('edit_category') == $category_id) : ?>
                <?= $this->render_partial('area/_edit_category_form', compact('category_id', 'categories')) ?>
            <? else : ?>
                <?= htmlReady($categories[$category_id]) ?>
            <? endif ?>
        </span>
    </caption>

    <colgroup>
        <col>
        <col>
        <col>
        <col>
        <col>
    </colgroup>

    <thead>
        <tr>
            <th colspan="2"> <?= _('Name des Bereichs') ?></th>
            <th data-type="answers"><?= _("Beitr�ge") ?></th>
            <th colspan="2" data-type="last_posting"><?= _("letzte Antwort") ?></th>
        </tr>
    </thead>


    <tbody class="sortable">

    <? if (!empty($entries)) foreach ($entries as $entry) : ?>
        <?= $this->render_partial('area/add', compact('entry')) ?>
    <? endforeach; ?>
    
    <? if ($category_id && ForumPerm::has('add_area', $seminar_id) && Request::get('add_area') == $category_id) : ?>
        <?= $this->render_partial('area/_add_area_form') ?>
    <? endif ?>
    
    <!-- this row allows dropping on otherwise empty categories -->
    <tr class="sort-disabled">
        <td class="areaborder" style="height: 5px; padding: 0px; margin: 0px"colspan="7"> </td>
    </tr>
    </tbody>

    <tfoot>
    <? if ($category_id && ForumPerm::has('add_area', $seminar_id)) : ?>
    <? if (Request::get('add_area') != $category_id) : ?>
    <tr class="add_area">
        <td colspan="5" onClick="STUDIP.Forum.addArea('<?= $category_id ?>'); return false;" class="add_area">
            <a href="<?= PluginEngine::getLink('coreforum/index/index/?add_area=' . $category_id)?>#cat_<?= $category_id ?>"  title="<?= _('Neuen Bereich zu dieser Kategorie hinzuf�gen.') ?>">
                <span><?= _('Bereich hinzuf�gen') ?></span>
                <?= Assets::img('icons/16/blue/add.png', array('id' => 'tutorAddArea')) ?>
            </a>
        </td>
    </tr>
    <? endif ?>
    <? endif ?>

    <!-- bottom border -->
    </tfoot>
</table>
<? endforeach ?>
</div>

<?= $this->render_partial('area/_js_templates') ?>
<?= $this->render_partial('joyride/areas') ?>
