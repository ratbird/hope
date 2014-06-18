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
            <? if ($course->veranstaltungsnummer) : ?>
                <tr>
                    <td><strong><?= _('Veranstaltungsnummer') ?></strong></td>
                    <td><?= htmlReady($course->veranstaltungsnummer) ?></td>
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
                    <td><strong><?= _("Heimat-Einrichtung") ?></strong></td>
                    <td>
                        <a href="<?= URLHelper::getScriptLink("dispatch.php/institute/overview", array('auswahl' => $course->institut_id)) ?>">
                            <?= htmlReady($course->home_institut->name) ?>
                        </a>
                    </td>
                </tr>
            <? if ($course->institutes->count() > 1): ?>
                <tr>
                    <td>
                        <strong><?= _('beteiligte Einrichtungen') ?></strong>
                    </td>
                    <td>
                        <?= join(', ', $course->institutes->orderBy('name')
                                       ->map(function($i) {
                                            return sprintf('<a href="%s">%s</a>', URLHelper::getScriptLink("dispatch.php/institute/overview", array('auswahl' => $i->id))
                                                                                , htmlReady($i->name));
                                })
                        ) ?>
                    </td>
                </tr>
            <? endif ?>
            <tr>
                <td><strong><?= _("Veranstaltungstyp") ?></strong></td>
                <td>
                    <?= sprintf(_("%s in der Kategorie %s"), $course->getSemType()->offsetGet('name'), $course->getSemClass()->offsetGet('name')) ?>
                </td>
            </tr>

            <? if ($prelim_discussion) : ?>
                <tr>
                    <td><strong><?= _('Vorbesprechung') ?></strong></td>
                    <td><?= $prelim_discussion ?></td>
                </tr>
            <? endif ?>
            <? $next_date = $sem->getNextDate() ?>
            <? if ($next_date) : ?>
                <tr>
                    <td><strong><?= _('Nächster Termin') ?></strong></td>
                    <td><?= $next_date ?></td>
                </tr>
            <? else : ?>
                <? $firstTerm = $sem->getFirstDate() ?>
                <? if ($firstTerm) : ?>
                    <tr>
                        <td><strong><?= _('Erster Termin') ?></strong></td>
                        <td><?= $firstTerm ?></td>
                    </tr>
                <? endif ?>
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
            <? foreach ($course->datafields->getTypedDatafield() as $entry) : ?>
                <? if ($entry->isVisible() && $entry->getValue()) : ?>
                    <tr>
                        <td><strong><?= htmlReady($entry->getName()) ?></strong></td>
                        <td>
                            <?= $entry->getDisplayValue() ?>
                        </td>
                    </tr>
                <? endif ?>
            <? endforeach ?>
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
            <?= $sem->getDatesHTML() ?>
        </section>
    </section>

    <section class="contentbox">
        <header>
            <h1><?= _('Veranstaltungsort') ?></h1>
        </header>
        <section>
            <?= $sem->getDatesTemplate('dates/seminar_html_location', array('ort' => $course->ort)) ?>
        </section>
    </section>

    <? if ($course->beschreibung) : ?>
        <section class="contentbox">
            <header>
                <h1><?= _("Kommentar/Beschreibung") ?></h1>
            </header>
            <section>
                <?= formatLinks($course->beschreibung) ?>
            </section>
        </section>
    <? endif ?>
    <? $lecturers = $course->members->findBy('status' ,'dozent'); ?>
    <? if (count($lecturers)) : ?>
        <section class="contentbox">
            <header>
                <h1><?= get_title_for_status('dozent', count($lecturers)) ?></h1>
            </header>
            <table class="default">
                <colgroup>
                    <col width="40%">
                </colgroup>
                <? foreach ($lecturers->orderBy('position name') as $lecturer) : ?>
                    <tr>
                        <td>
                            <a href="<?= URLHelper::getScriptLink('dispatch.php/profile', array('username' => $lecturer['username'])) ?>">
                                <?= htmlReady($lecturer->getUserFullname() . ($lecturer->label ? " (" . $lecturer->label . ")" : "")) ?>
                            </a>
                        </td>
                        <td style="text-align: right">
                            <a href="<?=
                            URLHelper::getScriptLink('dispatch.php/messages/write',
                                array('rec_uname' => $lecturer['username']))?>">
                                <?= Assets::img('icons/16/blue/mail.png', array('title' => _("Nachricht schreiben"))) ?>
                            </a>
                        </td>
                    </tr>
                <? endforeach ?>
            </table>
        </section>
    <? endif ?>

    <? $tutors = $course->members->findBy('status' ,'tutor'); ?>
    <? if (count($tutors)) : ?>
        <section class="contentbox">
            <header>
                <h1><?= get_title_for_status('tutor', count($tutors)) ?></h1>
            </header>
            <table class="default">
                <colgroup>
                    <col width="40%">
                </colgroup>
                <? foreach ($tutors->orderBy('position name') as $tutor) : ?>
                    <tr>
                        <td>
                            <a href="<?= URLHelper::getScriptLink('dispatch.php/profile', array('username' => $tutor['username'])) ?>">
                                <?= htmlReady($tutor->getUserFullname() . ($tutor->label ? " (" . $tutor->label . ")" : "")) ?>
                            </a>
                        </td>
                        <td style="text-align: right">
                            <a href="<?=
                            URLHelper::getScriptLink('dispatch.php/messages/write',
                                array('rec_uname' => $tutor['username']))?>">
                                <?= Assets::img('icons/16/blue/mail.png', array('title' => _("Nachricht schreiben"))) ?>
                            </a>
                        </td>
                    </tr>
                <? endforeach ?>
            </table>
        </section>
    <? endif ?>

    <? if ($this->studymodules) : ?>

        <section class="contentbox">
            <header>
                <h1><?= _('Studienmodule') ?></h1>
            </header>
            <section>
                <ul>
                    <? foreach ($this->studymodules as $module) : ?>
                        <li>
                            <a class="module-info" href="<?= URLHelper::getLink($module['nav']->getUrl())?>">
                                <?= htmlReady($module['title']) ?>
                                <? if ($module['nav']->getImage()) : ?>
                                    <img
                                    <? array_walk($module['nav']->getImage(), function (&$v,$k) {printf('%s="%s" ', $k, htmlReady($v));});?>
                                    >
                                <? endif ?>
                                <span><?= htmlReady($module['nav']->getTitle())?></span>
                            </a>
                        </li>
                    <? endforeach ?>
                </ul>
            </section>
        </section>
    <? endif ?>

    <? if ($study_areas) : ?>

        <section class="contentbox">
            <header>
                <h1><?= _('Studienbereiche') ?></h1>
            </header>
            <section>
                <ul class='css_tree'>
                    <?= $this->render_partial('study_area/tree.php', array('node' => $studyAreaTree, 'open' => true)) ?>
                </ul>
            </section>
        </section>
    <? endif ?>

    <? if ($courseset = $sem->getCourseSet()) : ?>
        <section class="contentbox">
            <header>
                <h1><?=_("Anmelderegeln")?></h1>
            </header>
            <section>
                <div>
                    <?= sprintf(_('Diese Veranstaltung gehört zum Anmeldeset "%s".'), htmlReady($courseset->getName())) ?>
                </div>
                <div id="courseset_<?= $courseset->getId() ?>">
                    <?= $courseset->toString(true) ?>
                </div>
            </section>
        </section>
    <? endif ?>

    <? if ($course->admission_prelim == 1) : ?>
        <section class="contentbox">
            <header>
                <h1><?= _('Anmeldemodus') ?></h1>
            </header>
            <section>
                <p><?= _("Die Auswahl der Teilnehmenden wird nach der Eintragung manuell vorgenommen.") ?></p>
                <? if ($course->admission_prelim_txt) : ?>
                    <p><?= formatReady($course->admission_prelim_txt) ?></p>
                <? else : ?>
                        <p><?=
                            _("NutzerInnen, die sich für diese Veranstaltung eintragen möchten,
                    erhalten nähere Hinweise und können sich dann noch gegen eine Teilnahme entscheiden.")?>
                        </p>
                <? endif ?>
            </section>
        </section>
    <? endif ?>

    <? if (!empty($course_domains)): ?>
        <section class="contentbox">
            <header>
                <h1><?= _("Zugelassenene Nutzerdomänen:") ?></h1>
            </header>
            <ul>
                <? foreach ($course_domains as $domain): ?>
                    <li><?= htmlReady($domain->getName()) ?></li>
                <? endforeach ?>
            </ul>
        </section>
    <? endif ?>

    <section class="contentbox">
        <header>
            <h1><?= _('Teilnehmerzahlen') ?></h1>
        </header>
        <table class="default">
            <colgroup>
                <col width="40%">
            </colgroup>
            <tbody>
            <tr>
                <td><?= _("Aktuelle Anzahl der Teilnehmenden") ?></td>
                <td><?= sprintf('%s', $course->getNumParticipants()) ?></td>
            </tr>
            <? if ($course->admission_turnout) : ?>
                <tr>
                    <td><?= $sem->isAdmissionEnabled() ? _("maximale Teilnehmeranzahl") : _("erwartete Teilnehmeranzahl")?></td>
                    <td><?= sprintf('%s', $course->admission_turnout) ?></td>
                </tr>
            <? endif ?>
            <? if ($sem->isAdmissionEnabled() && $course->getNumWaiting()) : ?>
                <tr>
                    <td><?= _("Wartelisteneinträge")?></td>
                    <td><?= sprintf('%s', $course->getNumWaiting()) ?></td>
                </tr>
            <? endif ?>
            </tbody>
        </table>
    </section>
