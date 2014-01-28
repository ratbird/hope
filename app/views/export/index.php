<form class="studip_form" method="POST">

    <? if ($templates): ?>
        <fieldset id="saved_templates">
            <legend><?= _("Vorlagen") ?></legend>
            <? foreach ($templates as $template): ?>
                <a href="<?= $template['export'] ?>">
                    <?= Assets::img("icons/16/blue/export/file.png") ?>
                    <?= htmlReady($template['name']) ?>
                </a>
                <a href='<?= $template['delete'] ?>' ><?= Assets::img('icons/12/blue/decline.png') ?></a>
                <br />
            <? endforeach; ?> 
        </fieldset>
    <? endif; ?>

    <fieldset id="export_as">
        <legend><?= _("Exportieren als") ?></legend>
        <? foreach ($formats as $format): ?>
            <a href="<?= $exportlink[$format] ?>">
                <?= Assets::img("icons/16/blue/file-" . $format . ".png") ?>
                <?= $format ?>
            </a><br />
        <? endforeach; ?>
    </fieldset>
    
    <? if ($templating): ?>
    <fieldset id="create_new_template">
        <legend><?= _("Neue Vorlagen anlegen") ?></legend>
        <label>Format
            <select name="format">
                <? foreach ($formats as $format): ?>
                    <option><?= $format ?></option>
                <? endforeach; ?>
            </select>
        </label>
        <label><?= _('Vorlagenname') ?>
            <input type="text" name="templatename" placeholder="<?= _('Vorlagenname') ?>">
        </label>

        <fieldset>
            <legend><?= _("Anpassbare Elemente") ?></legend>
            <? foreach ($preview as $pref): ?>
                <?= $pref ?>
            <? endforeach; ?>
        </fieldset>

        <input type="hidden" name="args" value='<?= $flash['args'] ?>'></input>
        <?= \Studip\Button::create(_("Anlegen"), 'create') ?>
    </fieldset>
    <? endif; ?>

</form>