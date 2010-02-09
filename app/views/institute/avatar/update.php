<? if (isset($error)) : ?>
    <?= MessageBox::error($error) ?>
<? endif ?>

<h1><?= _("Einrichtungssbild hochladen") ?></h1>

<div style="float: left; padding: 0 1em 1em 0;">
    <?= InstituteAvatar::getAvatar($institute_id)->getImageTag(Avatar::NORMAL) ?>
</div>

<form enctype="multipart/form-data"
      action="<?= $controller->url_for('institute/avatar/put/' . $institute_id) ?>"
      method="post" style="float: left">
    <input type="hidden" name="MAX_FILE_SIZE" value="1000000">
    <label for="upload-input"><?= _("Wählen Sie ein Bild für die Einrichtung:") ?></label>
    <input id="upload-input" name="avatar" type="file">

    <p class="quiet">
        <?= Assets::img("info.gif", array('style' => 'vertical-align: middle;')) ?>
        <? printf(_("Die Bilddatei darf max. %d KB groß sein, es sind nur Dateien mit den Endungen %s, %s oder %s erlaubt!"),
                  Avatar::MAX_FILE_SIZE / 1024,
                  '<b>.jpg</b>', '<b>.png</b>', '<b>.gif</b>') ?>
    </p>

    <p>
        <?= makeButton('absenden', 'input') ?>
        <span class="quiet">
            <?= _("oder") ?>
            <a href="<?= URLHelper::getLink('admin_institut.php?i_id=' . $institute_id) ?>">
            <?= makeButton('abbrechen') ?>
            </a>
        </span>
    </p>
</form>

