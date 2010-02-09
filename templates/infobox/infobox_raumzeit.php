<table align="center" width="250" border="0" cellpadding="0" cellspacing="0">

  <!-- Bild -->
  
  <tr>
    <td class="infobox" width="100%" align="right">
      <img src="<?=$GLOBALS['ASSETS_URL']?>images/<?=$picture?>">
    </td>
  </tr>

  <tr>
    <td class="infoboxrahmen" width="100%">
    <table background="<?=$GLOBALS['ASSETS_URL']?>images/white.gif" align="center" width="99%" border="0" cellpadding="4" cellspacing="0">

      <!-- Statusmeldungen -->
      <? if ($messages) :
            // render status messages partial  
            echo $this->render_partial("infobox/infobox_statusmessages_partial.php"); 
         endif; 
      ?>
            
      <!-- Informationen -->
    
      <tr>
        <td class="infobox" width="100%" colspan="2">
          <font size="-1"><b><?=_("Informationen")?>:</b></font>
          <br>
        </td>
      </tr>

      <tr>
          <td class="infobox" align="center" valign="top" width="1%">
            <img src="<?=$GLOBALS['ASSETS_URL']?>images/ausruf_small.gif">
          </td>
          <td class="infobox" width="99%" align="left">
            <font size="-1"><?=_("Hier können Sie alle Termine der Veranstaltung verwalten.")?></font>
            <br>
          </td>
      </tr>                             
                
      <!-- Semesterauswahl -->
      
      <?       
        // render "semesterauswahl" selection list partial  
        echo $this->render_partial("infobox/infobox_dropdownlist_partial.php"); 
      ?>


      <? if ($GLOBALS['RESOURCES_ENABLE'] && $GLOBALS['RESOURCES_ENABLE_BOOKINGSTATUS_COLORING']) : ?>
      
      <!-- Legende -->

      <tr>
        <td class="infobox" width="100%" colspan="2">
          <font size="-1"><b>Legende:</b></font>
          <br>
        </td>
      </tr>
    
      
        <tr>
          <td class="infobox" width="1%" align="center" valign="top">
            <img src="<?=$GLOBALS['ASSETS_URL']?>/images/plastic_red_small.jpg" height="20" width="25" alt="">
          </td>
          <td class="infobox" width="99%" align="left">
            <font size="-1"><?=_("Kein Termin hat eine Raumbuchung!")?></font>
            <br>
          </td>
    
        </tr>
    
      
        <tr>
          <td class="infobox" width="1%" align="center" valign="top">
            <img src="<?=$GLOBALS['ASSETS_URL']?>/images/plastic_yellow_small.jpg" height="20" width="25" alt="">
          </td>
          <td class="infobox" width="99%" align="left">
            <font size="-1"><?=_("Mindestens ein Termin hat keine Raumbuchung!")?></font>
            <br>
    
          </td>
        </tr>

  
        <tr>
          <td class="infobox" width="1%" align="center" valign="top">
            <img src="<?=$GLOBALS['ASSETS_URL']?>/images/plastic_green_small.jpg" height="20" width="25" alt="">
          </td>
          <td class="infobox" width="99%" align="left">
            <font size="-1"><?=_("Alle Termine haben eine Raumbuchung.")?></font>
    
            <br>
          </td>
        </tr>

        <? endif; ?>
                
    </table>
    </td>
  </tr>
</table>

