  <tr>
    <td width="100%" colspan="2">
      <b><?= $selectionlist_title ?>:</b>
      <br>
    </td>
  </tr>

  <? for ($i = 0; $i < count($selectionlist); $i++) : ?>
    <? if ( $selectionlist[$i]["is_selected"] ) : ?>
    <tr>
      <td width="1%" align="center" valign="top">
        <img src="<?= $GLOBALS['ASSETS_URL'] ?>images/icons/16/red/arr_1right.png">
      </td>
      <td width="99%" align="left">
        <a href="<?= $selectionlist[$i]["url"] ?>"><?= $selectionlist[$i]["linktext"] ?></a>
        <br>
      </td>
    </tr>
    <? else: ?>
    <tr>
      <td width="1%" align="center" valign="top">
        <img src="<?= $GLOBALS['ASSETS_URL'] ?>images/icons/16/black/arr_1right.png">
      </td>
      <td width="99%" align="left">
        <a href="<?= $selectionlist[$i]["url"] ?>"><?= $selectionlist[$i]["linktext"] ?></a>
        <br>
      </td>
    </tr>
    <? endif; ?>

  <? endfor; ?>
