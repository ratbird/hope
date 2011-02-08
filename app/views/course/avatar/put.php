<?
# Lifter010: TODO
?>
<h1><?= _("Veranstaltungsbild hochladen") ?></h1>

<?= MessageBox::success(_("Die Bilddatei wurde erfolgreich hochgeladen.")) ?>

<p class="quiet">
    <?= _("Eventuell sehen Sie das neue Bild erst, nachdem Sie diese Seite neu geladen haben (in den meisten Browsern F5 drücken).") ?>
</p>

<p>
    <? if ($this->studygroup_mode) : ?>
    <a href="<?= URLHelper::getLink('dispatch.php/course/studygroup/edit/' . $course_id) ?>">
      <?= Assets::img('icons/16/blue/arr_1left.png', array('style' => 'vertical-align: baseline;')) ?>
      <?= _("zurück zur Studiengruppenadministration") ?>
    </a>
    <? else: ?>
    <a href="<?= URLHelper::getLink('dispatch.php/course/basicdata/view/' . $course_id) ?>">
      <?= Assets::img('icons/16/blue/arr_1left.png', array('style' => 'vertical-align: baseline;')) ?>
      <?= _("zurück zur Veranstaltungsadministration") ?>
    </a>
    <? endif ?>
</p>
