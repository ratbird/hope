<?
# Lifter010: TODO

use Studip\Button, Studip\LinkButton;

$sem = Seminar::getInstance($show_entry['id']);
?>
<div id="edit_sem_entry" class="schedule_edit_entry">
    <div id="edit_sem_entry_drag" class="window_heading"><?=_("Veranstaltungsdetails bearbeiten")?></div>
    <form action="<?= $controller->url_for('calendar/schedule/editseminar/'. $show_entry['id'] .'/'. $show_entry['cycle_id'] ) ?>" method="post" name="edit_entry" style="padding-left: 10px; padding-top: 10px; margin-right: 10px;">
        <?= CSRFProtection::tokenTag() ?>
        <b><?= _("Farbe des Termins") ?>:</b>
        <? foreach ($GLOBALS['PERS_TERMIN_KAT'] as $data) : ?>
        <span style="background-color: <?= $data['color'] ?>; vertical-align: middle; padding-top: 3px;">
            <input type="radio" name="entry_color" value="<?= $data['color'] ?>" <?= ($data['color'] == $show_entry['color']) ? 'checked="checked"' : '' ?>>
        </span>
        <? endforeach ?>

        <br><br>

        <? if ($show_entry['type'] == 'virtual') : ?>
            <span style="color: red; font-weight: bold"><?= _("Dies ist lediglich eine vorgemerkte Veranstaltung") ?></span><br><br>
        <? endif ?>

        <b><?= _("Veranstaltungsnummer") ?>:</b>
        <?= htmlReady($sem->getNumber()) ?><br><br>

        <b><?= _("Name") ?>:</b>
        <?= htmlReady($sem->getName()) ?><br><br>


        <b><?= _("Dozenten") ?>:</b>
        <? $pos = 0;foreach ($sem->getMembers('dozent') as $dozent) :
            if ($pos > 0) echo ', ';
            ?><a href="<?= URLHelper::getLink('about.php?username=' . $dozent['username']) ?>"><?= htmlReady($dozent['fullname']) ?></a><?
            $pos++;
        endforeach ?>
        <br><br>

        <b><?= _("Veranstaltungszeiten") ?>:</b><br>
        <?= $sem->getDatesHTML(array('show_room' => true)) ?><br>

        <?= Assets::img('icons/16/blue/link-intern.png') ?>
        <? if ($show_entry['type'] == 'virtual') : ?>
        <a href="<?= URLHelper::getLink('details.php?sem_id='. $show_entry['id']) ?>"><?=_("Zur Veranstaltung") ?></a><br>
        <? else : ?>
        <a href="<?= URLHelper::getLink('seminar_main.php?auswahl='. $show_entry['id']) ?>"><?=_("Zur Veranstaltung") ?></a><br>
        <? endif ?>
        <br>

        <div style="text-align: center">
            <?= Button::createAccept(_('Speichern'), array('style' => 'margin-right: 20px')) ?>

            <? if (!$show_entry['visible']) : ?>
                <?= LinkButton::create(_('Einblenden'),
                                       $controller->url_for('calendar/schedule/bind/'. $show_entry['id'] .'/'. $show_entry['cycle_id'] .'/'. '?show_hidden=1'), 
                                       array('style' => 'margin-right: 20px')) ?>
            <? else : ?>
                <?= LinkButton::create($show_entry['type'] == 'virtual' ? _('Löschen') : _('Ausblenden'),
                                       $controller->url_for('calendar/schedule/unbind/'. $show_entry['id'] .'/'. $show_entry['cycle_id']),
                                       array('style' => 'margin-right: 20px')) ?>
            <? endif ?>

            <?= LinkButton::createCancel(_('Abbrechen'),
                                         $controller->url_for('calendar/schedule'),
                                         array('onclick' => "jQuery('#edit_sem_entry').fadeOut('fast'); STUDIP.Calendar.click_in_progress = false; return false")) ?>
        </div>
    </form>
</div>
<script>
    jQuery('#edit_sem_entry').draggable({ handle: 'edit_sem_entry_drag' });
</script>
