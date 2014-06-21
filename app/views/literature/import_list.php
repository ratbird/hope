<? use Studip\Button, Studip\LinkButton; ?>
<? if ($msg) : ?>
    <table width="99%" border="0" cellpadding="2" cellspacing="0">
        <?=parse_msg ($msg,"§","blank",1,false)?>
    </table>
    <br>
<? endif ?>
    <?= CSRFProtection::tokenTag() ?>
    <? if ($plugin_name) : ?>
        <form enctype="multipart/form-data" class="studip_form" action="<?= URLHelper::getLink('dispatch.php/literature/edit_list?_range_id='.$return_range) ?>" method="post">
            <fieldset>
                <legend><?= _("Datei hochladen") ?></legend>
                <br>
                <?= strlen($plugin["description"]) > 0 ? Assets::img('icons/16/grey/info-circle.png') : '' ?>
                <?= formatReady($plugin["description"]) ?><br>
                <br>
                <?= _("Wählen Sie mit <b>Durchsuchen</b> eine Datei von Ihrer Festplatte aus.") ?><br>
                <input type="hidden" name="cmd" value="import_lit_list">
                <input type="hidden" name="plugin_name" value="<?=$plugin_name?>">
                <input name="xmlfile" type="file" style="width:250px" accept="text/xml" maxlength="8000000"><br>
                <?= Button::createAccept(_('Importieren'), array('data-dialog-button' => ''))?>
            </fieldset>
        </form>
    <? else : ?>
        <form enctype="multipart/form-data" class="studip_form" action="<?= URLHelper::getLink('dispatch.php/literature/import_list') ?>" method="post">
            <fieldset>
                <legend><?= _("Format wählen") ?></legend>
                <label for="plugin_name">
                    <?= _("Bitte wählen Sie eine Literaturverwaltung aus:"); ?>
                </label>
                <input type="hidden" name="cmd" value="">
                <select name="plugin_name" size="1" onChange="jQuery('#lit_choose_plugin').click();">
                <? foreach ($GLOBALS['LIT_IMPORT_PLUGINS'] as $p) : ?>
                    <option value="<?= $p["name"] ?>" <?= ($p["name"]==$plugin_name ? "selected" : "") ?>>
                        <?= $p["visual_name"] ?>
                    </option>
                <? endforeach; ?>
                </select>
                <?= Button::createAccept(_('Auswählen'), array('id' => 'lit_choose_plugin', 'data-dialog' => '', 'data-dialog-button' => ''))?>
            </fieldset>
        </form>
    <? endif; ?>