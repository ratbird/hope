        <tr>
          <td class="infobox" width="100%" colspan="2">
            <font size="-1"><b><?=_("Statusmeldungen")?>:</b></font>
            <br>
          </td>
        </tr>
      
          <? for ($i = 0; $i < count($messages); $i++) : 
                // TODO: die Datenstruktur sollte noch "huebscher" aufgebaut werden, 
                //       als einfaches 2D-Array, dass nicht mehr zerlegt werden muss
                //       vorerst ist sie hier nunmal so
                $message = explode('§', $messages[$i]);
          ?>        
            <? // select, which kind of message it is 
              switch ($message[0]) {
                case 'info':
                    $message_icon = "ausruf_small2.gif";
                    $message_color = '#000000';
                    break;
                case 'error':
                    $message_icon = "x_small2.gif";
                    $message_color = '#FF2020';
                    break;
                case 'msg':
                    $message_icon = "ok_small2.gif";
                    $message_color = '#008000';
                    break;
                } ?>
           <tr>
            <td class="infobox effect_highlight" width="1%" align="center" valign="top">
              <img src="<?= $GLOBALS['ASSETS_URL']."images/".$message_icon ?>">
            </td>
            <td class="infobox effect_highlight" width="99%" align="left">
               <font size="-1"><font color="<?=$message_color?>"><?=$message[1]?></font>
               <br>
            </td>
           </tr>      
          <? endfor; ?>

