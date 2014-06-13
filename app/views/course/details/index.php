<? $sem = Seminar::GetInstance($course->seminar_id) ?>

<? if (!Request::isXhr()) : ?>
    <h1>
        <?= htmlReady($course->name) ?>
        <? if ($course->untertitel) : ?>
            <span><?= htmlReady($course->untertitel) ?></span>
        <? endif ?>
    </h1>
<? endif ?>
    <section class="contentbox">
        <header>
            <h1><?= _('Allgemeine Informationen') ?></h1>
        </header>
        <table class="default">
            <colgroup>
                <col width="40%">
            </colgroup>
            <tbody>
            <? if (is_object($institut)) : ?>
                <tr>
                    <td><strong><?= _("Heimat-Einrichtung") ?></strong></td>
                    <td>
                        <a href="<?= URLHelper::getLink("institut_main.php", array('auswahl' => $course->institut_id)) ?>">
                            <?= htmlReady($institut->name) ?>
                        </a>
                    </td>
                </tr>
            <? endif ?>
            <tr>
                <td><strong><?= _("Veranstaltungstyp") ?></strong></td>
                <td>
                    <?= sprintf(_("%s in der Kategorie %s"), $GLOBALS['SEM_TYPE'][$course->status]["name"], $GLOBALS['SEM_CLASS'][$GLOBALS['SEM_TYPE'][$course->status]["class"]]["name"]) ?>
                </td>
            </tr>
            <? if ($course->veranstaltungsnummer) : ?>
                <tr>
                    <td><strong><?= _("Veranstaltungsnummer") ?></strong></td>
                    <td>
                        <?= $course->veranstaltungsnummer ?>
                    </td>
                </tr>
            <? endif ?>
            <? if ($course->start_semester): ?>
                <tr>
                    <td>
                        <strong><?= _('Semester') ?></strong>
                    </td>
                    <td>
                        <?= htmlReady($course->getFullname('sem-duration-name')) ?>
                    </td>
                </tr>
            <? endif ?>
            <tr>
                <td><strong><?= _('Vorbesprechung') ?></strong></td>
                <td><?= ($prelim_discussion ? $prelim_discussion : _('Keine Vorbesprechung')) ?></td>
            </tr>
            <? $firstTerm = $sem->getFirstDate() ?>
            <? if ($firstTerm) : ?>
                <tr>
                    <td><strong><?= _('Erster Termin') ?></strong></td>
                    <td><?= $firstTerm ?></td>
                </tr>
            <? endif ?>
            <? if ($course->art) : ?>
                <tr>
                    <td><strong><?= _("Art/Form") ?></strong></td>
                    <td><?= htmlReady($course->art) ?></td>
                </tr>
            <? endif ?>
            <? if ($course->teilnehmer != "") : ?>
                <tr>
                    <td><strong><?= _("Teilnehmende") ?></strong></td>
                    <td>
                        <?= htmlReady($course->teilnehmer, true, true) ?>
                    </td>
                </tr>
            <? endif ?>
            <? if ($course->vorrausetzungen != "") : ?>
                <tr>
                    <td><strong><?= _("Voraussetzungen") ?></strong></td>
                    <td>
                        <?= htmlReady($course->vorrausetzungen, true, true) ?>
                    </td>
                </tr>
            <? endif ?>
            <? if ($course->lernorga != "") : ?>
                <tr>
                    <td><strong><?= _("Lernorganisation") ?></strong></td>
                    <td>
                        <?= htmlReady($course->lernorga, true, true) ?>
                    </td>
                </tr>
            <? endif ?>
            <? if ($course->leistungsnachweis != "") : ?>
                <tr>
                    <td><strong><?= _("Leistungsnachweis") ?></strong></td>
                    <td>
                        <?= htmlReady($course->leistungsnachweis, true, true) ?>
                    </td>
                </tr>
            <? endif ?>
            <? $localEntries = DataFieldEntry::getDataFieldEntries($course->id); ?>
            <? if (!empty($localEntries)) : ?>
                <? foreach ($localEntries as $entry) : ?>
                    <? if ($entry->structure->accessAllowed($GLOBALS['perm'])) : ?>
                        <? if ($entry->getValue()) : ?>
                            <tr>
                                <td><strong><?= htmlReady($entry->getName()) ?></strong></td>
                                <td>
                                    <?= $entry->getDisplayValue() ?>
                                </td>
                            </tr>
                        <? endif ?>
                    <? endif ?>
                <? endforeach ?>
            <? endif ?>
            <? if ($course->sonstiges != "") : ?>
                <tr>
                    <td><strong><?= _("Sonstiges") ?></strong></td>
                    <td>
                        <?= formatLinks($course->sonstiges) ?>
                    </td>
                </tr>
            <? endif ?>
            <? if ($course->ects) : ?>
                <tr>
                    <td><strong><?= _("ECTS-Punkte") ?></strong></td>
                    <td>
                        <?= htmlReady($course->ects, true, true) ?>
                    </td>
                </tr>
            <? endif ?>
            </tbody>
        </table>
    </section>


    <section class="contentbox">
        <header>
            <h1><?= _('Zeiten') ?></h1>
        </header>
        <section>
            <? if (!empty($cycle_dates)) : ?>
                <ul class="list-unstyled">
                    <? foreach ($this->cycle_dates as $date) : ?>
                        <li><?= $date->toString('full'); ?></li>
                    <? endforeach ?>
                </ul>
            <? else : ?>
                <p><?= _("Die Zeiten der Veranstaltung stehen nicht fest.") ?></p>
            <? endif ?>
        </section>
    </section>

    <section class="contentbox">
        <header>
            <h1><?= _('Veranstaltungsort') ?></h1>
        </header>
        <section>
            <? $places = $sem->getDatesTemplate('dates/seminar_html_location', array('ort' => $seminar['Ort'])) ?>
            <? if ($places != 'nicht angegeben') : ?>
                <?= $places ?>
            <? else : ?>
                <p><?= _("Die Orte der Veranstaltung stehen nicht fest.") ?></p>
            <? endif ?>
        </section>
    </section>

<? if (!empty($teachers)) : ?>
    <section class="contentbox">
        <header>
            <h1><?= get_title_for_status('dozent', count($teachers)) ?></h1>
        </header>
        <table class="default">
            <colgroup>
                <col width="40%">
            </colgroup>
            <? foreach ($teachers as $teacher) : ?>
                <? $fullname = $teacher['vorname'] . ' ' . $teacher['nachname'] ?>
                <tr>
                    <td>
                        <a href="<?= URLHelper::getLink('dispatch.php/profile', array('username' => $teacher['username'])) ?>">
                            <?= htmlReady($fullname) ?>
                        </a>
                    </td>
                    <td style="text-align: right">
                        <a href="<?=
                        URLHelper::getLink('dispatch.php/messages/write',
                            array('filter'    => 'send_sms_to_all',
                                  'rec_uname' => $teacher['username']))?>">
                            <?= Assets::img('icons/16/blue/mail.png', tooltip2(sprintf(_('Eine Nachricht an %s schreiben'), $fullname))) ?>
                        </a>
                    </td>
                </tr>
            <? endforeach ?>
        </table>
    </section>
<? endif ?>

<? if (!empty($tutors)) : ?>
    <section class="contentbox">
        <header>
            <h1><?= get_title_for_status('tutor', count($tutors)) ?></h1>
        </header>
        <table class="default">
            <colgroup>
                <col width="40%">
            </colgroup>
            <? foreach ($tutors as $tutor) : ?>
                <? $fullname = $tutor['vorname'] . ' ' . $tutor['nachname'] ?>
                <tr>
                    <td>
                        <a href="<?= URLHelper::getLink('dispatch.php/profile', array('username' => $tutor['username'])) ?>">
                            <?= htmlReady($fullname) ?>
                        </a>
                    </td>
                    <td style="text-align: right">
                        <a href="<?=
                        URLHelper::getLink('dispatch.php/messages/write',
                            array('filter'    => 'send_sms_to_all',
                                  'rec_uname' => $tutor['username']))?>">
                            <?= Assets::img('icons/16/blue/mail.png', tooltip2(sprintf(_('Eine Nachricht an %s schreiben'), $fullname))) ?>
                        </a>
                    </td>
                </tr>
            <? endforeach ?>
        </table>
    </section>
<? endif ?>

<? if (!empty($study_path)) : ?>

    <section class="contentbox">
        <header>
            <h1><?= _('Studienbereiche') ?></h1>
        </header>
        <section>
            <ul>
                <? foreach ($study_path as $path) : ?>
                    <li><?= htmlReady($path['name']) ?></li>
                <? endforeach ?>
            </ul>
        </section>
    </section>
<? endif ?>

<? if ((int)$course->admission_prelim == 1) : ?>
    <section class="contentbox">
        <header>
            <h1><?= _('Anmeldeverfahren') ?></h1>
        </header>
        <section>
            <p><?= _("Die Auswahl der Teilnehmenden wird nach der Eintragung manuell vorgenommen.") ?></p>
            <? if ($admission_participation) : ?>
                <p><?= formatReady($course->admission_prelim_txt) ?></p>
            <? else : ?>
                <? if (!$GLOBALS['perm']->have_perm("admin")) : ?>
                    <p><?=
                        _("Wenn Sie an der Veranstaltung teilnehmen wollen, klicken Sie auf \"Tragen Sie sich hier
                ein\". Sie erhalten dann nähere Hinweise und kännen sich immer noch gegen eine Teilnahme
                entscheiden.")?></p>
                <? else : ?>
                    <p><?=
                        _("NutzerInnen, die sich für diese Veranstaltung eintragen möchten,
                erhalten nähere Hinweise und können sich dann noch gegen eine Teilnahme entscheiden.")?>
                    </p>
                <? endif ?>
            <? endif ?>
        </section>
    </section>

    <? if (!empty($course_domains)): ?>
        <section class="contentbox">
            <header>
                <h1><?= _("Zugelassenene Nutzerdomänen:") ?></h1>
            </header>
            <ul class="list-unstyled">
                <? foreach ($course_domains as $domain): ?>
                    <li><?= htmlReady($domain->getName()) ?></li>
                <? endforeach ?>
            </ul>
        </section>
    <? endif ?>

    <? if (!empty($courseset)) : ?>
        <section class="contentbox">
            <header>
                <h1><?= sprintf(_('Diese Veranstaltung gehört zum Anmeldeset "%s".'), htmlReady($courseset->getName())) ?></h1>
            </header>
            <section>
                <div id="courseset_<?= $courseset->getId() ?>">
                    <?= $courseset->toString(true) ?>
                </div>
            </section>
        </section>
    <? endif ?>
<? endif ?>

    <section class="contentbox">
        <header>
            <h1><?= _('Statistik') ?></h1>
        </header>
        <table class="default">
            <colgroup>
                <col width="40%">
            </colgroup>
            <tbody>
            <tr>
                <td><?= _("Anzahl der Teilnehmenden") ?></td>
                <td style="text-align: right"><?= sprintf('%s', ($statistics['count'] != 0) ? $statistics['count'] : _("keine")) ?></td>
            </tr>
            <tr>
                <td><?= get_title_for_status('dozent', $statistics['anz_dozent'], $course->status) ?></td>
                <td style="text-align: right"><?= $statistics['anz_dozent'] ? : _('keine') ?></td>
            </tr>
            <tr>
                <td><?= get_title_for_status('tutor', $statistics['anz_tutor'], $course->status) ?></td>
                <td style="text-align: right"><?= $statistics['anz_tutor'] ? : _('keine') ?></td>
            </tr>
            <tr>
                <td><?= get_title_for_status('autor', $statistics['anz_autor'], $course->status) ?></td>
                <td style="text-align: right"><?= $statistics['anz_autor'] ? : _('keine') ?></td>
            </tr>
            <tr>
                <td><?= _('Forenbeiträge') ?></td>
                <td style="text-align: right"><?= ($statistics['forumPosts']) ? : _('keine') ?></td>
            </tr>
            <tr>
                <td><?= _('Dokumente') ?></td>
                <td style="text-align: right"><?= ($statistics['documents']) ? : _('keine') ?></td>
            </tr>
            </tbody>
        </table>
    </section>

<? if (!Request::isXhr()) {
    $sidebar = Sidebar::Get();
    $sidebar->setImage('sidebar/seminar-sidebar.png');
    $sidebar->setContextAvatar(CourseAvatar::getAvatar($course->id));

}
?>