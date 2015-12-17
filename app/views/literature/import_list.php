<? use Studip\Button, Studip\LinkButton; ?>
<? if (count($plugin)) : ?>
    <form enctype="multipart/form-data" class="studip_form" action="<?= URLHelper::getLink('dispatch.php/literature/edit_list?_range_id='.$return_range) ?>" method="post">
        <?= CSRFProtection::tokenTag() ?>
        <fieldset>
            <legend><?= _("Datei hochladen") ?></legend>
            <br>
            <?= strlen($plugin["description"]) > 0 ? Icon::create('info-circle', 'inactive')->asImg(16) : '' ?>
            <?= formatReady($plugin["description"]) ?><br>
            <br>
            <?= _("Wählen Sie mit <b>Durchsuchen</b> eine Datei von Ihrer Festplatte aus.") ?><br>
            <input type="hidden" name="cmd" value="import_lit_list">
            <input type="hidden" name="plugin_name" value="<?=htmlReady($plugin['name'])?>">
            <input name="xmlfile" type="file"><br>
            <?= Button::createAccept(_('Importieren'), array('data-dialog-button' => ''))?>
        </fieldset>
    </form>
<? else : ?>
    <form class="studip_form" action="<?= URLHelper::getLink('dispatch.php/literature/import_list?return_range=' . $return_range) ?>" method="post">
        <?= CSRFProtection::tokenTag() ?>
        <fieldset>
            <legend><?= _("Format wählen") ?></legend>
            <label for="plugin_name">
                <?= _("Bitte wählen Sie eine Literaturverwaltung aus:"); ?>
            </label>
            <input type="hidden" name="cmd" value="">
            <select name="plugin_name" size="1" onChange="jQuery('#lit_choose_plugin').click();">
            <? foreach ($GLOBALS['LIT_IMPORT_PLUGINS'] as $p) : ?>
                <option value="<?= htmlReady($p["name"]) ?>">
                    <?= htmlReady($p["visual_name"]) ?>
                </option>
            <? endforeach; ?>
            </select>
            <?= Button::createAccept(_('Auswählen'), array('id' => 'lit_choose_plugin', 'data-dialog' => '', 'data-dialog-button' => ''))?>
        </fieldset>
    </form>
<? endif; ?>
