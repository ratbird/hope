<?
# Lifter010: TODO
?>
<!-- upload extern config -->
<tr>
    <td colspan="10" align="center" class="<?= $class ?>">
        <a name="upload"></a>

        <div align="left" style="border: 1px solid #000000; padding: 3px; width: 95%">
            <b><?= _("Maximale Gr&ouml;&szlig;e:") ?> <?= ($max_filesize / 1024) ?></b> <?= _("Kilobyte") ?><br>

            <form enctype="multipart/form-data" name="upload_form" action="<?= UrlHelper::getLink() ?>" method="post">
                <?= CSRFProtection::tokenTag() ?>
                <div width="100%" class="steelgraudunkel" style="padding : 2px; margin: 10px 0px 10px 0px">
                    <?= _("1. Klicken Sie auf <b>'Durchsuchen...'</b>, um eine Datei auszuw&auml;hlen.") ?>
                </div>
                &nbsp;<?= _("Dateipfad:") ?><br>
                &nbsp;<input name="the_file" type="file"  style="width: 70%"><br>

                <div width="100%" class="steelgraudunkel" style="padding : 2px; margin: 10px 0px 10px 0px">
                    <?= _("2. Klicken Sie auf <b>'absenden'</b>, um die Datei hochzuladen.") ?>
                </div>
                <input type="image" <?= makeButton("absenden", "src") ?> align="absmiddle" onClick="return STUDIP.OldUpload.upload_start(jQuery(this).closest('form'));" name="create" border="0">
                &nbsp;<a href="<?= URLHelper::getLink('?cancel_x=true') ?>"><?= makeButton("abbrechen", "img") ?></a>

                <input type="hidden" name="com" value="do_upload_config">
                <input type="hidden" name="check_module" value="<?= $module ?>">
                <input type="hidden" name="config_id" value="<?= $config_id ?>">
            </form>

        </div>
    </td>
</tr>
<!-- end of upload extern config -->
