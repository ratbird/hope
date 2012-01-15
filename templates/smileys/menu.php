<table align="center">
    <tr>
        <td class="smiley_th"><?= $text ?></td>
    <? foreach ($first_chars as $char => $label): ?>
        <td <?= $fc == $char ? 'class="smiley_redborder"' : '' ?>>
            &nbsp;<a href="<?= URLHelper::getLink('?fc=' . $char) ?>"><?= $label ?></a>&nbsp;
        </td>
    <? endforeach; ?>
    <? if ($GLOBALS['auth']->auth['jscript']): ?>
        <td class="smiley_th">
            &nbsp;<a href="#" onclick="window.close()"><?= _('Fenster schließen') ?></a>&nbsp;
        </td>
    <? endif; ?>
    </tr>
</table>