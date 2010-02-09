<table align="center" width="250" border="0" cellpadding="0" cellspacing="0">

  <!-- Bild -->
  
  <tr>
    <td class="infobox" width="100%" align="right">
      <?= Assets::img('groups.jpg') ?>
    </td>
  </tr>

  <tr>
    <td class="infoboxrahmen" width="100%">
    <table background="<?=Assets::url('images/white.gif')?>" align="center" width="99%" border="0" cellpadding="4" cellspacing="0">

      <!-- Statusmeldungen -->
      <? if ($messages) :
            // render status messages partial  
            echo $this->render_partial("infobox/infobox_statusmessages_partial.php", array('messages', $message)); 
         endif; 
      ?>
            
      <!-- Aktionen -->
    
      <tr>
        <td class="infobox" width="100%" colspan="2">
          <font size="-1"><b><?=_("Aktionen")?>:</b></font>
          <br>
        </td>
      </tr>

      <tr>
				<td class="infobox" align="center" width="1%" valign="top">
					<?= Assets::img('link_intern') ?>
				</td>
				<td class="infobox" width="99%" align="left">
					<a href="<?= $help_url=format_help_url("Basis.EinrichtungenVerwaltenGruppen") ?>">
						<?= _("Bedienungshinweise in der Hilfe") ?>
					</a>
				</td>
      </tr>

      <tr>
          <td class="infobox" align="center" width="1%" valign="top">
          	<?= Assets::img('link_intern') ?>
          </td>
          <td class="infobox" width="99%" align="left">
            <font size="-1">
				<a href="<?= URLHelper::getLink('?cmd=newRole&range_id='. $range_id) ?>"><?= _("neue Gruppe anlegen") ?></a>
			</font>
          </td>
      </tr>

      <tr>
          <td class="infobox" align="center" width="1%" valign="top">
          	<?= Assets::img('link_intern') ?>
          </td>
          <td class="infobox" width="99%" align="left">
            <font size="-1">
				<a href="<?= URLHelper::getLink('?view=sort&range_id='. $range_id) ?>"><?= _("Gruppenreihenfolge ändern") ?></a>
			</font>
          </td>
      </tr>

    </table>
    </td>
  </tr>
</table>

