<?=_("Diese Email wurde automatisch vom Stud.IP-System verschickt. Sie können auf diese Nachricht nicht antworten.")
   . "\n" . _("Sie erhalten hiermit in regelmäßigen Abständen Informationen über Neuigkeiten und Änderungen in Ihren abonnierten Veranstaltungen.")
   . "\n\n" . _("Über welche Inhalte Sie informiert werden wollen, können Sie hier einstellen:")
   . "\n".$GLOBALS['ABSOLUTE_URI_STUDIP'].URLHelper::getURL('sem_notification.php')."\n\n"?>
<? foreach ($news as $sem_titel=>$data) : ?>
<?=sprintf(_("In der Veranstaltung \"%s\" gibt es folgende Neuigkeiten:"),$sem_titel)?>

<?=$GLOBALS['ABSOLUTE_URI_STUDIP'].URLHelper::getURL('seminar_main.php?auswahl='.$data[0]['range_id'])?>


<? foreach ($data as $module) : ?>
<?=$module['txt']?>

<?=$module['url']?>

<? endforeach ?>

<? endforeach ?>

--
<?=_("Diese Nachricht wurde automatisch vom Stud.IP-System generiert. Sie können darauf nicht antworten.")?>
