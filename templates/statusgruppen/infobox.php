<?
# Lifter010: TODO
?>
<table class="infobox" align="center" width="250" border="0" cellpadding="0" cellspacing="0">

  <!-- Bild -->

  <tr>
    <td class="infobox-img">
      <?= Assets::img('sidebar/group-sidebar.png') ?>
    </td>
  </tr>

  <tr>
    <td class="infoboxrahmen" width="100%">
    <table align="center" width="99%" border="0" cellpadding="4" cellspacing="0">

      <!-- Aktionen -->

      <tr>
        <td width="100%" colspan="2">
          <b><?=_("Aktionen")?>:</b>
          <br>
        </td>
      </tr>

      <tr>
        <td align="center" width="1%" valign="top">
            <?= Icon::create('info', 'info')->asImg() ?>
        </td>
        <td width="99%" align="left">
            <a href="<?= $help_url=format_help_url("Basis.EinrichtungenVerwaltenGruppen") ?>" target="_blank">
                <?= _("Bedienungshinweise in der Hilfe") ?>
            </a>
        </td>
      </tr>
      <? if (!LockRules::Check($range_id, 'groups')) :?>
      <tr>
          <td align="center" width="1%" valign="top">
            <?= Icon::create('community+add', 'info')->asImg() ?>
          </td>
          <td width="99%" align="left">

                <a href="<?= URLHelper::getLink('?cmd=newRole&range_id='. $range_id) ?>"><?= _("neue Gruppe anlegen") ?></a>

          </td>
      </tr>

      <tr>
          <td align="center" width="1%" valign="top">
            <?= Icon::create('refresh', 'info')->asImg() ?>
          </td>
          <td width="99%" align="left">

                <a href="<?= URLHelper::getLink('?view=sort&range_id='. $range_id) ?>"><?= _("Gruppenreihenfolge �ndern") ?></a>

          </td>
      </tr>
      <? endif;?>
    </table>
    </td>
  </tr>
</table>

