<?
# Lifter010: TODO
?>
<?= _("Diese E-Mail wurde automatisch vom Stud.IP-System verschickt. Sie k�nnen auf diese Nachricht nicht antworten.") ?>

<?= _("Sie erhalten hiermit in regelm��igen Abst�nden Informationen �ber Neuigkeiten und �nderungen in Ihren abonnierten Veranstaltungen.") ?>


<?= _("�ber welche Inhalte Sie informiert werden wollen, k�nnen Sie hier einstellen:") ?>

<?= URLHelper::getURL('dispatch.php/settings/notification') ?>


<? foreach ($news as $sem_titel => $data) : ?>
<?= sprintf(_("In der Veranstaltung \"%s\" gibt es folgende Neuigkeiten:"), $sem_titel) ?>

<?= URLHelper::getURL('seminar_main.php?again=yes&auswahl=' . $data[0]['range_id']) ?>


<? foreach ($data as $module) : ?>
<?= $module['text'] ?>

<?= URLHelper::getURL($module['url']) ?>

<? endforeach ?>

<? endforeach ?>

-- 
<?= _("Diese Nachricht wurde automatisch vom Stud.IP-System generiert. Sie k�nnen darauf nicht antworten.") ?>
