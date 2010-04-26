<table align="center" width="250" border="0" cellpadding="0" cellspacing="0">

  <!-- Bild -->

  <tr>
    <td class="infobox" width="100%" align="right">
      <?= Assets::img('infoboxes/groups.jpg') ?>
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
          <td class="infobox" align="center" width="1%" valign="top">
            <?= Assets::img('ausruf_small') ?>
          </td>
          <td class="infobox" width="99%" align="left">
            <font size="-1">
                            <?= _("Hier können sie ihre Kontaktdaten für die Einrichtungen angeben, an denen Sie tätig sind."); ?>
                        </font>
          </td>
      </tr>

    </table>
    </td>
  </tr>
</table>

