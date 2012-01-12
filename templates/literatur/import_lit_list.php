<?
# Lifter010: TODO
use Studip\Button, Studip\LinkButton;
?>
<p class="info">
    <form enctype="multipart/form-data" action="<?= URLHelper::getLink('?_range_id='. $_range_id .'&username='. $username) ?>" method="post">
        <?= CSRFProtection::tokenTag() ?>
        <input type="hidden" name="cmd" value="import_lit_list">
        <table border="0" cellspacing="0" cellpadding="0">
            <tr>
                <td class="steelkante" height="20"><b><?= _("Literaturlisten importieren:") ?></b></td>
            </tr>
            <tr>
                <td class="steel1">&nbsp;</td>
            </tr>
            <tr>
          <td class="steel1" style="padding-left: 10px">
                    <?= _("Bitte w&auml;hlen Sie eine Literaturverwaltung aus:"); ?>
            <select name="plugin_name" size="1" onChange="this.form.cmd='';this.form.submit();">
                    <? foreach ($GLOBALS['LIT_IMPORT_PLUGINS'] as $p) : ?>
                  <option value="<?= $p["name"] ?>" <?= ($p["name"]==$plugin_name ? "selected" : "") ?>>
                                <?= $p["visual_name"] ?>
                </option>
                    <? endforeach; ?>
            </select>

            <? if ($plugin_name) : ?>
                <br>
                <?= strlen($plugin["description"]) > 0 ? Assets::img('icons/16/grey/info-circle.png') : '' ?>
                <?= formatReady($plugin["description"]) ?><br>
                <br>
                <?= _("1. W&auml;hlen Sie mit <b>Durchsuchen</b> eine Datei von Ihrer Festplatte aus.") ?><br>
        <input name="xmlfile" type="file" style="width:250px" accept="text/xml" maxlength="8000000"><br>
                <br>
                <?= _("2. Klicken Sie auf <b>absenden</b>, um die Datei hochzuladen.") ?><br>
                <br>
        <?= Button::createAccept(_('absenden'))?>
            <? endif; ?>
                <br>
                <br>
                </td>
            </tr>
      </table>
    </form>
</p>
