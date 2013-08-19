<table class="index_box">
      <colgroup>
    <col width="33%">
    <col width="33%">
    <col width="33%">
  </colgroup>
    <tr>
        <td class="table_header_bold"><?= _("Vorlagen") ?></td>
        <td class="table_header_bold"><?= _("Neue Vorlagen anlegen") ?></td>
        <td class="table_header_bold"><?= _("Direkt Exportieren") ?></td>
    </tr>
    <tr>
        <td>
            <? foreach ($templates as $template): ?>
                <a href="<?= $template['export'] ?>">
                    <?= Assets::img("icons/16/blue/export/file.png") ?>
                    <?= htmlReady($template['name']) ?>
                </a>
                <a href='<?= $template['delete'] ?>' ><?= Assets::img('icons/12/blue/decline.png') ?></a>
                <br />
            <? endforeach; ?>          
        </td>
        <td>
            <form name="exportform" action="<?= $savelink ?>" method="post">
                <select name="format">
                    <? foreach ($formats as $format): ?>
                        <option><?= $format ?></option>
                    <? endforeach; ?>
                </select>
                <input name="templatename" />
                
                <?= \Studip\Button::create(_("Anlegen")) ?>
        </td>
        <td>
            <? foreach ($formats as $format): ?>
                <a href="<?= $exportlink[$format] ?>">
                    <?= Assets::img("icons/16/blue/file-" . $format . ".png") ?>
                    <?= $format ?>
                </a><br />
            <? endforeach; ?>
        </td>
    </tr>
</table>

<? foreach ($preview as $pref): ?>
    <?= $pref ?>
<? endforeach; ?>
<input type="hidden" name="args" value='<?= $flash['args'] ?>'></input>
</form>
