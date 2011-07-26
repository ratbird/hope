<?
# Lifter010: TODO
?>
<table class="infobox" align="center" width="250" border="0" cellpadding="0" cellspacing="0">

  <!-- Bild -->

  <tr>
    <td class="infobox-img">
      <?= Assets::img('infobox/groups.jpg') ?>
    </td>
  </tr>

  <tr>
    <td class="infoboxrahmen" width="100%">
    <table align="center" width="99%" border="0" cellpadding="4" cellspacing="0">

      <!-- Informationen -->

      <tr>
        <td width="100%" colspan="2">
          <b><?=_("Information")?>:</b>
          <br>
        </td>
      </tr>
      <tr>
        <td align="center" width="1%" valign="top">
            <?= Assets::img('icons/16/black/info.png') ?>
        </td>
        <td width="99%" align="left">
            <?= _("Wenn bei einer Gruppe der Selbsteintrag aktivert ist, können sich Teilnehmer selbst eintragen und austragen.")?>
        </td>
      </tr>
      <tr>
                <td align="center" width="1%" valign="top">
                    <?= Assets::img('icons/16/black/info.png') ?>
                </td>
                <td width="99%" align="left">
                    <?
                    $help_url = format_help_url("Basis.VeranstaltungenVerwaltenGruppen");
                    ?>
                    <a href="<?= $help_url ?>" target="_blank">
                        <?= _("Bedienungshinweise in der Hilfe") ?>
                    </a>
                </td>
      </tr>
      <tr>
        <td width="100%" colspan="2">
          <b><?=_("Aktionen")?>:</b>
          <br>
        </td>
      </tr>


      <tr>
                <td align="center" width="1%" valign="top">
                     <?= ($self_assign_all) ? Assets::img('icons/16/black/checkbox-checked.png') : Assets::img('icons/16/black/checkbox-unchecked.png') ?>
                </td>
                <td width="99%" align="left">
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
                <td align="center" width="1%" valign="top">
                    <?= ($self_assign_exclusive) ? Assets::img('icons/16/black/checkbox-checked.png') : Assets::img('icons/16/black/checkbox-unchecked.png') ?>
                </td>
                <td width="99%" align="left">
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

