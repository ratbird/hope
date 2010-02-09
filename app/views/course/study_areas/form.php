<div id="study_area_selection">

  <input type="hidden" name="study_area_selection[last_selected]" value="<?= htmlReady($selection->getSelected()->getID()) ?>">
  <input type="hidden" name="study_area_selection[showall]" value="<?= (int) $selection->getShowAll() ?>">

  <input type="submit" name="study_area_selection[placeholder]" style="display:none;">


  <div id="study_area_selection_chosen">

    <h3><?= _("Bestehende Zuordnungen:") ?></h3>

    <? if ($selection->size()) : ?>
      <div id="study_area_selection_none" style="display:none;"><?= _("Bisher wurde noch keine Zuordnung vorgenommen") ?></div>
    <? else: ?>
      <div id="study_area_selection_none"><?= _("Bisher wurde noch keine Zuordnung vorgenommen") ?></div>
    <? endif ?>

    <div id="study_area_selection_at_least_one" style="display:none;">
      <?= _("Sie können diesen Studienbereich nicht löschen, da eine Veranstaltung immer mindestens einem Studienbereich zugeordnet sein muss.") ?>
    </div>

    <?= $this->render_partial('course/study_areas/selected_entries') ?>

  </div>


  <div id="study_area_selection_selectables">

    <h3><?= _("Bitte wählen:") ?></h3>

    <?= $this->render_partial('course/study_areas/tree', compact('trail', 'subtree')) ?>

    <h3><?=_("Suche:")?></h3>

    <input type="text" name="study_area_selection[search_key]" value="">
    <input type="image" name="study_area_selection[search_button]" src="<?= Assets::image_path('suche2.gif') ?>">

    <? if ($selection->searched()) : ?>
      <a href="<?= URLHelper::getLink(isset($url) ? $url : '',
                      array('study_area_selection[rewind_button]' => 1,
                            'study_area_selection[last_selected]' => $selected,
                           'study_area_selection[showall]' => (int) $selection->getShowAll())) ?>">
        <?= Assets::img('rewind.gif') ?>
      </a>

      <? if (!sizeof($selection->getSearchResult())) : ?>
        <em><?= sprintf(_("Der Suchbegriff '%s' lieferte kein Ergebnis."), htmlReady($selection->getSearchKey())) ?></em>
      <? else : ?>
        <h3><?= _("Suchergebnisse:") ?></h3>
        <? TextHelper::reset_cycle(); $show_path = TRUE; $show_link = TRUE; ?>
        <? foreach ($selection->getSearchResult() as $area) : ?>
          <div class="<?= TextHelper::cycle('odd', 'even') ?>">
            <?= $this->render_partial('course/study_areas/entry', compact('area', 'show_path', 'show_link')); ?>
          </div>
        <? endforeach ?>
      <? endif ?>
    <? endif ?>

  </div>
</div>

