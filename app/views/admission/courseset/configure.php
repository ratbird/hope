<?php
use Studip\Button, Studip\LinkButton;

//Infobox:
$info = array();
$info[] = array(
              "icon" => "icons/16/black/info.png",
              "text" => "Hier können Sie die Regeln, Eigenschaften und ".
                        "Zuordnungen des Anmeldesets bearbeiten.");
$info[] = array(
              "icon" => "icons/16/black/info.png",
              "text" => "Sie können das Anmeldeset allen Einrichtungen zuordnen, ".
                        "an denen Sie mindestens Dozentenrechte haben.");

$info[] = array(
              "icon" => "icons/16/black/info.png",
              "text" => "Alle Veranstaltungen der Einrichtungen, an denen Sie ".
                        "mindestens Dozentenrechte haben, können zum ".
                        "Anmeldeset hinzugefügt werden.");

$infobox = array(
    array("kategorie" => _('Informationen:'),
          "eintrag" => $info
    )
);
$infobox = array('content' => $infobox,
                 'picture' => 'sidebar/admin-sidebar.png'
);

// Load assigned course IDs.
$courseIds = $courseset ? $courseset->getCourses() : array();
// Load assigned user list IDs.
$userlistIds = $courseset ? $courseset->getUserlists() : array();

if ($flash['error']) {
    echo MessageBox::error($flash['error']);
}
?>
<?= $this->render_partial('dialog/confirm_dialog') ?>
<h1><?= $courseset ? _('Anmeldeset bearbeiten') : _('Anmeldeset anlegen') ?></h1>
<form class="studip_form" action="<?= $controller->url_for(!$instant_course_set_view ? 'admission/courseset/save/' . ($courseset ? $courseset->getId() : '') : 'course/admission/save_courseset/' . $courseset->getId()) ?>" method="post">
    <fieldset>
        <legend><?= _('Grunddaten') ?></legend>
        <label for="name" class="caption">
            <?= _('Name des Anmeldesets:') ?>
            <span class="required">*</span>
        </label>
        <input type="text" size="60" maxlength="255" name="name"
            value="<?= $courseset ? htmlReady($courseset->getName()) : '' ?>"
            required="required" aria-required="true"/>
        <? if (!$courseset || ($courseset->getUserId() == $GLOBALS['user']->id && !$instant_course_set_view)) : ?>
            <label for="private" class="caption">
                <?= _('Sichtbarkeit:') ?>
            </label>
            <input type="checkbox" name="private"<?= $courseset ? ($courseset->getPrivate() ? ' checked="checked"' : '') : 'checked' ?>/>
            <?= _('Dieses Anmeldeset soll nur für mich selbst sichtbar und benutzbar sein.') ?>
        <?  endif ?>
        <label for="institutes" class="caption">
            <?= _('Einrichtungszuordnung:') ?>
            <span class="required">*</span>
        </label>
        <? if (!$instant_course_set_view) : ?>
            <div id="institutes">
            <?php if ($myInstitutes) { ?>
                <?php if ($instSearch) { ?>
                    <?= $instTpl ?>
                <?php } else { ?>
                    <?php foreach ($myInstitutes as $institute) { ?>
                        <?php if (sizeof($myInstitutes) != 1) { ?>
                    <input type="checkbox" name="institutes[]" value="<?= $institute['Institut_id'] ?>"
                        <?= $selectedInstitutes[$institute['Institut_id']] ? 'checked="checked"' : '' ?>
                        class="institute" onclick="STUDIP.Admission.getCourses(
                        '<?= $controller->url_for('admission/courseset/instcourses', $courseset ? $courseset->getId() : '') ?>')"/>
                        <?php } else { ?>
                    <input type="hidden" name="institutes[]" value="<?= $institute['Institut_id'] ?>"/>
                        <?php } ?>
                        <?= htmlReady($institute['Name']) ?>
                    <br/>
                    <?php } ?>
                <?php } ?>
            <?php } else { ?>
                <?php if ($instSearch) { ?>
                <div id="institutes">
                    <?= Assets::img('icons/16/yellow/arr_2down.png', array(
                        'alt' => _('Einrichtung hinzufügen'),
                        'title' => _('Einrichtung hinzufügen'),
                        'onclick' => "STUDIP.Admission.updateInstitutes($('#institute_id_1_realvalue').val(), '". 
                            $controller->url_for('admission/courseset/institutes', $courseset ? $courseset->getId() : '')."', '".
                            $controller->url_for('admission/courseset/instcourses', $courseset ? $courseset->getId() : '')."', 'add')")) ?>
                    <?= $instSearch ?>
                    <?= Assets::img('icons/16/blue/search.png', array('title' => _("Suche starten")))?>
                </div>
                <i><?=  _('Sie haben noch keine Einrichtung ausgewählt. Benutzen Sie obige Suche, um dies zu tun.') ?></i>
                <?php } else { ?>
                <i><?=  _('Sie sind keiner Einrichtung zugeordnet.') ?></i>
                <?php } ?>
            <?php } ?>
            </div>
        <? else : ?>
            <? foreach (array_keys($selectedInstitutes) as $institute) : ?>
                <?= htmlReady($myInstitutes[$institute]['Name']) ?>
                <br>
            <?  endforeach ?>
        <?  endif ?>
    </fieldset>
    <fieldset>
        <legend><?= _('Veranstaltungen') ?></legend>
        <? if (!$instant_course_set_view) : ?>
            <label class="caption">
                <?= _('Semester:') ?>
                <select name="semester" onchange="STUDIP.Admission.getCourses('<?= $controller->url_for('admission/courseset/instcourses', $courseset ? $courseset->getId() : '') ?>')">
                    <?php foreach(array_reverse(Semester::getAll(), true) as $id => $semester) { ?>
                    <option value="<?= $id ?>"<?= $id == $selectedSemester ? ' selected="selected"' : '' ?>>
                        <?= htmlReady($semester->name) ?>
                    </option>
                    <?php } ?>
                </select>
            </label>
            <label class="caption">
                <?= _('Filter auf Name/Nummer/Dozent:') ?><br>
                <input style="display:inline-block" type="text" onKeypress="if (event.which==13) return STUDIP.Admission.getCourses('<?= $controller->url_for('admission/courseset/instcourses', $courseset ? $courseset->getId() : '') ?>')" value="<?= htmlReady($current_course_filter) ?>" name="course_filter" >
                <?=Assets::img('icons/16/blue/search.png', array('title' => _("Veranstaltungen anzeigen"),'onClick' => "return STUDIP.Admission.getCourses('" . $controller->url_for('admission/courseset/instcourses', $courseset ? $courseset->getId() : '') ."')"))?>
            </label>
            <div id="instcourses">
            <?= $coursesTpl; ?>
            </div>
            <? if (count($courseIds) && $courseset->getAdmissionRule('ParticipantRestrictedAdmission')) : ?>
                <div>
                        <?= LinkButton::create(_('Ausgewählte Veranstaltungen konfigurieren'),
                            $controller->url_for('admission/courseset/configure_courses/' . $courseset->getId()),
                            array('data-dialog' => '')
                            ); ?>
                        <? if ($num_applicants = $courseset->getNumApplicants()) :?>
                        <?= LinkButton::create(sprintf(_('Liste der Anmeldungen (%s Nutzer)'), $num_applicants),
                            $controller->url_for('admission/courseset/applications_list/' . $courseset->getId()),
                            array('data-dialog' => '')
                            ); ?>
                        <? endif ?>
                </div>
            <? endif ?>
        <? else :?>
            <? if (count($courseIds) > 100) :?>
                <?= sprintf(_("%s zugewiesene Veranstaltungen"), count($courseIds)) ?>
            <? else : ?>
                <? foreach ($courseIds as $course_id) : ?>
                    <?= htmlReady(Course::find($course_id)->name) ?>
                    <br>
                <?  endforeach ?>
            <? endif ?>
        <? endif ?>
    </fieldset>
    <fieldset>
        <legend><?= _('Anmelderegeln') ?></legend>
        <div id="rules">
            <?php if ($courseset) { ?>
            <div id="rulelist">
                <?php foreach ($courseset->getAdmissionRules() as $rule) { ?>
                    <?= $this->render_partial('admission/rule/save', array('rule' => $rule)) ?>
                <?php } ?>
            </div>
            <?php } else { ?>
            <span id="norules">
                <i><?= _('Sie haben noch keine Anmelderegeln festgelegt.') ?></i>
            </span>
            <br/>
            <?php } ?>
            <div style="clear: both;">
                    <?= LinkButton::create(_('Anmelderegel hinzufügen'),
                        $controller->url_for('admission/rule/select_type' . ($courseset ? '/'.$courseset->getId() : '')),
                        array(
                            'onclick' => "return STUDIP.Admission.selectRuleType(this)"
                            )
                        ); ?>
            </div>
        </div>
    </fieldset>
    <? if (!$instant_course_set_view) : ?>
    <fieldset>
        <legend><?= _('Weitere Daten') ?></legend>
    <? if ($courseset && $courseset->getSeatDistributionTime()) :?>
        <label class="caption">
            <?= _('Nutzerlisten zuordnen:') ?>
            </label>
            <?php if ($myUserlists) { ?>
                <?php
                foreach ($myUserlists as $list) {
                    $checked = '';
                    if (in_array($list->getId(), $userlistIds)) {
                        $checked = ' checked="checked"';
                    }
                ?>
                <input type="checkbox" name="userlists[]" value="<?= $list->getId() ?>"<?= $checked ?>/> <?= $list->getName() ?><br/>
                <?php } ?>

            <?php } else { ?>
                <i><?=  _('Sie haben noch keine Nutzerlisten angelegt.') ?></i>
            <?php
            }?>
            <? if ($courseset) : ?>
            <div>
                    <?= LinkButton::create(_('Liste der Nutzer'),
                        $controller->url_for('admission/courseset/factored_users/' . $courseset->getId()),
                        array('data-dialog' => '')
                        ); ?>
            </div>
            <? endif ?>
            <?php
            // Keep lists that were assigned by other users.
            foreach ($userlistIds as $list) {
                if (!in_array($list, array_keys($myUserlists))) {
            ?>
            <input type="hidden" name="userlists[]" value="<?= $list ?>"/>
            <?php
                }
            }
            ?>
        <? endif ?>
        <label for="infotext" class="caption">
            <?= _('Weitere Hinweise:') ?>
        </label>
        <textarea cols="60" rows="3" name="infotext"><?= $courseset ? htmlReady($courseset->getInfoText()) : '' ?></textarea>
    </fieldset>
    <? endif ?>
        <div class="submit_wrapper" data-dialog-button>
            <?= CSRFProtection::tokenTag() ?>
            <?= Button::createAccept(_('Speichern'), 'submit') ?>
            <?= LinkButton::createCancel(_('Abbrechen'), $controller->url_for('admission/courseset')) ?>
        </div>

</form>