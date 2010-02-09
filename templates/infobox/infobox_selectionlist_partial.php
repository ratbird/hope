  <tr>
    <td class="infobox" width="100%" colspan="2">
      <font size="-1"><b><?= $selectionlist_title ?>:</b></font>
      <br>
    </td>
  </tr>
  
  <? for ($i = 0; $i < count($selectionlist); $i++) : ?>
    <? if ( $selectionlist[$i]["is_selected"] ) : ?>
    <tr>
      <td class="infobox" width="1%" align="center" valign="top">
        <img src="<?= $GLOBALS['ASSETS_URL'] ?>images/forumrot.gif">
      </td>
      <td class="infobox" width="99%" align="left">
        <font size="-1"><a href="<?= $selectionlist[$i]["url"] ?>"><?= $selectionlist[$i]["linktext"] ?></a></font>
        <br>
      </td>    
    </tr>
    <? else: ?>
    <tr>
      <td class="infobox" width="1%" align="center" valign="top">
        <img src="<?= $GLOBALS['ASSETS_URL'] ?>images/forumgrau.gif">
      </td>
      <td class="infobox" width="99%" align="left">
        <font size="-1"><a href="<?= $selectionlist[$i]["url"] ?>"><?= $selectionlist[$i]["linktext"] ?></a></font>
        <br>
      </td>    
    </tr>
    <? endif; ?>
    
  <? endfor; ?>