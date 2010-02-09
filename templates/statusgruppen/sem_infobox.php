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
            
      <!-- Informationen -->
    
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
					<?
					if (get_config("EXTERNAL_HELP")) {
						$help_url=format_help_url("Basis.VeranstaltungenVerwaltenGruppen");
					} else {
						$help_url="help/index.php?referrer_page=admin_statusgruppe.php";
					}
					?>
					<a href="<?= $help_url ?>">
						<?= _("Bedienungshinweise in der Hilfe") ?>
					</a>
				</td>
      </tr>

      <tr>
				<td class="infobox" align="center" width="1%" valign="top">
					<?= Assets::img('link_intern') ?>
				</td>
				<td class="infobox" width="99%" align="left">
					<? if ($self_assign_all) : ?>
					<?= sprintf(_("Selbsteintrag in allen Gruppen ist %seingeschaltet%s."), '<b>', '</b>'); ?>
					<a href="<?= URLHelper::getLink('?cmd=deactivateSelfAssignAll'); ?>"><?= _("Ausschalten") ?></a>
					<? else : ?>
					<?= sprintf(_("Selbsteintrag in allen Gruppen ist %sausgeschaltet%s."), '<b>', '</b>'); ?>
					<a href="<?= URLHelper::getLink('?cmd=activateSelfAssignAll'); ?>"><?= _("Einschalten") ?></a>
					<? endif; ?>
				</td>
      </tr>

      <tr>
				<td class="infobox" align="center" width="1%" valign="top">
					<?= Assets::img('link_intern') ?>
				</td>
				<td class="infobox" width="99%" align="left">
					<? if ($self_assign_exclusive) : ?>
					<?= sprintf(_("Selbsteintrag in nur einer Gruppe erlauben ist %seingeschaltet%s."), '<b>', '</b>'); ?>
					<a href="<?= URLHelper::getLink('?cmd=deactivateSelfAssignExclusive'); ?>"><?= _("Ausschalten") ?></a>
					<? else : ?>
					<?= sprintf(_("Selbsteintrag in nur einer Gruppe erlauben ist %sausgeschaltet%s."), '<b>', '</b>'); ?>
					<a href="<?= URLHelper::getLink('?cmd=activateSelfAssignExclusive'); ?>"><?= _("Einschalten") ?></a>
					<? endif; ?>
				</td>
      </tr>

    </table>
    </td>
  </tr>
</table>

