<html>
<head>
<?= Assets::stylesheet('style.css') ?>
</head>
<body>
<center>
<table style="text-align:left; width:700px; min-width:700px; max-width:700px; background-color:white;">
  <tr>
    <td style="height:90px; min-height:90px; max-height:90px;">
      <img src="<?= $GLOBALS['ASSETS_URL'] ?>images/locale/<?= $lang ?>/LC_PICTURES/mail_header_notification.png">
    </td>
  </tr>
  <tr>
    <td>
      <span style="font-size:12px;"><?= _("Sie erhalten hiermit in regelmäßigen Abständen Informationen über Neuigkeiten und Änderungen in Ihren abonnierten Veranstaltungen.") ?><br/><br/>
        <?=_("Über welche Inhalte und in welchem Format Sie informiert werden wollen, können Sie hier einstellen:")?><br/>
        <a href="<?= $GLOBALS['ABSOLUTE_URI_STUDIP'] . URLHelper::getURL('sem_notification.php') ?>"><?= $GLOBALS['ABSOLUTE_URI_STUDIP'] . URLHelper::getURL('sem_notification.php') ?></a><br/><br/>
      </span>
      <table border=0 style="width:700px;">
<? foreach ($news as $sem_titel => $data) : ?>
        <tr>
          <td colspan="2" class="topic" style="font-size:14px; font-weight:bold;">
            <a style="text-decoration:none; color:white;" href="<?= $GLOBALS['ABSOLUTE_URI_STUDIP'] . URLHelper::getURL('seminar_main.php?auswahl=' . $data[0]['range_id']) ?>"><?= htmlReady($sem_titel) ?><?= (($semester = get_semester($n['range_id'])) ? ' ('.$semester.')' : '') ?></a>
          </td>
        </tr>
<? foreach ($data as $module) : ?>
<? $cssSw->switchClass(); ?>
        <tr>
          <td class="<?= $cssSw->getClass()?>" style="font-size:12px;">
            <a style="text-decoration:none;" href="<?=$module['url']?>"><?=htmlReady($module['txt'])?></a>
          </td>
          <td class="<?= $cssSw->getClass() ?>" style="width:25px; text-align:center;">
            <a href="<?= $module['url'] ?>"><?= Assets::img($module['icon'], array('alt' => htmlReady($module['txt']), 'title' => htmlReady($module['txt']))) ?></a>
          </td>
        </tr>
<? endforeach ?>
<? endforeach ?>
      </table>
    </td>
  </tr>
  <tr>
    <td>
      <hr>
      <span style="font-size:10px;"><?= _("Diese Nachricht wurde automatisch vom Stud.IP-System generiert. Sie können darauf nicht antworten.") ?></span>
    </td>
  </tr>
</table>
</center>
</body>
</html>
