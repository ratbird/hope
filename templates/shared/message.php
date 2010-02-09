<tr>
  <td class="<?= $class ?> effect_highlight" colspan="<?= $colspan ?>">
    <table border="0" cellspacing="0" cellpadding="2">
      <tr>
        <td align="center" width="<?= $width ?>">
          <img src="<?= $GLOBALS['ASSETS_URL'] ?>images/<?= $pic ?>">
        </td>
        <td align="left">
          <font color="<?= $color ?>" <?= $small ? 'size="-1"' : '' ?>><?= $msg ?></font>
        </td>
      </tr>
    </table>
  </td>
</tr>
<? if ($add_row) : ?>
<tr>
  <td class="<?= $class ?>" colspan="<?= $colspan ?>">&nbsp;</td>
</tr>
<? endif ?>
