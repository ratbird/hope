<?
# Lifter010: TODO
?>
<h1><?= _("Einrichtungsbild hochladen") ?></h1>

<?= MessageBox::success(_("Die Bilddatei wurde erfolgreich hochgeladen.")) ?>

<p class="quiet">
    <?= _("Eventuell sehen Sie das neue Bild erst, nachdem Sie diese Seite neu geladen haben (in den meisten Browsern F5 drücken).") ?>
</p>

<p>
    <a href="<?= URLHelper::getLink('dispatch.php/institute/basicdata/index?i_view=' . $institute_id) ?>">
      <?= Assets::img('icons/16/blue/arr_1left.png', array('style' => 'vertical-align: baseline;')) ?>
      <?= _("zurück zur Einrichtungsadministration") ?>
    </a>
</p>
