<html>
<head>
  <?= Assets::stylesheet('style.css') ?>
</head>
<body style="background: none;">
  <?= Assets::img("locale/$lang/LC_PICTURES/mail_header_notification.png") ?>
  <p>
    <?= _("Sie erhalten hiermit in regelmäßigen Abständen Informationen über Neuigkeiten und Änderungen in Ihren abonnierten Veranstaltungen.") ?>
    <br><br>
    <?= _("Über welche Inhalte und in welchem Format Sie informiert werden wollen, können Sie hier einstellen:") ?>
    <br>
    <a href="<?= URLHelper::getLink('sem_notification.php') ?>"><?= URLHelper::getLink('sem_notification.php') ?></a>
  </p>

  <table class="default" style="max-width: 750px;">
    <? foreach ($news as $sem_titel => $data) : ?>
      <tr>
        <th colspan="2">
          <a href="<?= URLHelper::getLink('seminar_main.php?again=yes&auswahl=' . $data[0]['range_id']) ?>">
            <?= htmlReady($sem_titel) ?>
            <?= (($semester = get_semester($data[0]['range_id'])) ? ' ('.$semester.')' : '') ?>
          </a>
        </th>
      </tr>

      <? foreach ($data as $module) : ?>
      <tr class="<?= TextHelper::cycle('cycle_odd', 'cycle_even') ?>">
        <td>
          <a href="<?= URLHelper::getLink($module['url']) ?>"><?= htmlReady($module['text']) ?></a>
        </td>
        <td>
          <a href="<?= URLHelper::getLink($module['url']) ?>"><?= Assets::img($module['icon'], array('title' => htmlReady($module['text']))) ?></a>
        </td>
      </tr>
      <? endforeach ?>
    <? endforeach ?>
  </table>
  <hr>
  <?= _("Diese Nachricht wurde automatisch vom Stud.IP-System generiert. Sie können darauf nicht antworten.") ?>
</body>
</html>
