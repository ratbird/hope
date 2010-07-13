<table class="infobox" align="center" width="250" border="0" cellpadding="0" cellspacing="0">

  <!-- Bild -->

  <tr>
    <td class="infobox-img">
      <?= Assets::img('infoboxes/groups.jpg') ?>
    </td>
  </tr>

  <tr>
    <td class="infoboxrahmen" width="100%">
    <table align="center" width="99%" border="0" cellpadding="4" cellspacing="0">

      <!-- Statusmeldungen -->
      <? if ($messages) :
            // render status messages partial
            echo $this->render_partial("infobox/infobox_statusmessages_partial.php", array('messages', $message));
         endif;
      ?>

      <!-- Aktionen -->

      <tr>
        <td width="100%" colspan="2">
          <b><?=_("Aktionen")?>:</b>
          <br>
        </td>
      </tr>

      <tr>
                <td align="center" width="1%" valign="top">
                    <?= Assets::img('link_intern') ?>
                </td>
                <td width="99%" align="left">
                    <a href="<?= $help_url=format_help_url("Basis.EinrichtungenVerwaltenGruppen") ?>">
                        <?= _("Bedienungshinweise in der Hilfe") ?>
                    </a>
                </td>
      </tr>

      <tr>
          <td align="center" width="1%" valign="top">
            <?= Assets::img('link_intern') ?>
          </td>
          <td width="99%" align="left">

                <a href="<?= URLHelper::getLink('?cmd=newRole&range_id='. $range_id) ?>"><?= _("neue Gruppe anlegen") ?></a>

          </td>
      </tr>

      <tr>
          <td align="center" width="1%" valign="top">
            <?= Assets::img('link_intern') ?>
          </td>
          <td width="99%" align="left">

                <a href="<?= URLHelper::getLink('?view=sort&range_id='. $range_id) ?>"><?= _("Gruppenreihenfolge ändern") ?></a>

          </td>
      </tr>

    </table>
    </td>
  </tr>
</table>

