<table>
    <tr>
        <td><h2><?= _("Vorlagen") ?></h2></td>
        <td><h2><?= _("Exportieren als") ?></h2></td>
    </tr>
    <tr>
        <td>
            <? foreach ($templates as $template): ?>
                <a href="<?= $template['export'] ?>">
                    <?= Assets::img("icons/16/blue/export/file.png") ?>
                    <?= $template['name'] ?>
                </a>
            <a href='<?= $template['delete'] ?>' ><?= Assets::img('icons/12/blue/decline.png') ?></a>
            <br />
            <? endforeach; ?>          
        </td>
        <td>
            <? foreach ($formats as $format): ?>
                <a href="<?= $exportlink[$format] ?>">
                    <?= Assets::img("icons/16/blue/file-".$format.".png") ?>
                    <?= $format ?>
                </a><br />
            <? endforeach; ?>
        </td>
    </tr>
    <tr>
        <td colspan="2"><h2><?= _("Neue Vorlagen anlegen") ?></h2></td>
    </tr>
    <tr>
        <td colspan="2">
            <form name="exportform" action="<?= $savelink ?>" method="post">
                <select name="format">
            <? foreach ($formats as $format): ?>
                    <option><?= $format ?></option>
            <? endforeach; ?>
                </select>
                <input name="templatename" />
                <?= \Studip\Button::create(_("Anlegen")) ?>
                <? foreach ($preview as $pref): ?>
                    <?= $pref ?>
                <? endforeach; ?>
                <input type="hidden" name="args" value='<?= $flash['args'] ?>'></input>
            </form>
        </td>
    </tr>
</table>

