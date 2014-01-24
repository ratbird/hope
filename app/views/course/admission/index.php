<h1><?= _("Zugangsberechtigungen") ?></h1>

<form class="studip_form" action="<?= $controller->link_for('/change_course_set') ?>" method="post">
<?= CSRFProtection::tokenTag() ?>
    <fieldset>
        <legend><?= _("Anmelderegeln")?></legend>
        <div>
            <?=_("Bitte geben Sie hier an, welche speziellen Anmelderegeln gelten sollen."); ?>
        </div>
        <? if ($current_courseset) : ?>
        <div>
            <?= sprintf(_('Diese Veranstaltung geh�rt zum Anmeldeset "%s".'), htmlReady($current_courseset->getName())) ?>
            <div id="courseset_<?= $current_courseset->getId() ?>">
                    <?= $current_courseset->toString(true) ?>
            </div>
            <div>
            <? if (!$is_locked['admission_type']) : ?>
                <?  if ($current_courseset->isUserAllowedToAssignCourse($user_id, $course_id)) : ?>
                    <?= Studip\Button::create(_("Zuordnung aufheben"), 'change_course_set_unassign', array('rel' => 'lightbox')) ?>
                <? endif ?>
                <? if ($current_courseset->isUserAllowedToEdit($user_id)) : ?>
                    <?= Studip\LinkButton::create(_("Anmeldeset bearbeiten"), $controller->url_for('/edit_courseset/' . $current_courseset->getId()), array('rel' => 'lightbox')); ?>
                <? endif ?>
            <? endif ?>
            </div>
        </div>
        <? else : ?>
            <? if (!$is_locked['admission_type'] && count($available_coursesets)) : ?>
            <label class="caption">
                <?=_("Zuordnung zu einem bestehenden Anmeldeset"); ?>
            </label>
            <select name="course_set_assign" style="display: inline-block;" 
                onChange="$('#course_set_assign_explain').load('<?= $controller->link_for('/explain_course_set') ?>&set_id=' + $(this).val());">
                <option></option>
                <? foreach($available_coursesets as $cs) : ?>
                    <option value="<?= $cs->getId() ?>"><?= htmlReady($cs->getName()) ?></option>
                <? endforeach ?>
            </select>
            <div id="course_set_assign_explain" style="display: inline-block;padding:1ex;">
            </div>
            <div style="display: inline-block;padding:1ex;">
                <?= Studip\Button::create(_("Zuordnen"), 'change_course_set_assign') ?>
            </div>
            <? endif ?>
            <label class="caption">
                <?=_("Anmelderegeln erzeugen"); ?>
            </label>
            <div>
            <? if (!$is_locked['passwort']) : ?>
                <?= Studip\LinkButton::create(_("Anmeldung mit Passwort"), $controller->url_for('/instant_course_set', array('type' => 'PasswordAdmission')),array('rel' => 'lightbox')) ?>
            <? endif ?> 
            <? if (!$is_locked['admission_type']) : ?>
                <?= Studip\LinkButton::create(_("Anmeldung gesperrt"), $controller->url_for('/instant_course_set', array('type' => 'LockedAdmission')),array('rel' => 'lightbox')) ?>
                <?= Studip\LinkButton::create(_("Zeitgesteuerte Anmeldung"), $controller->url_for('/instant_course_set', array('type' => 'TimedAdmission')),array('rel' => 'lightbox')) ?>
                <br>
                <?= Studip\LinkButton::create(_("Teilnahmebeschr�nkte Anmeldung"), $controller->url_for('/instant_course_set', array('type' => 'ParticipantRestrictedAdmission')),array('rel' => 'lightbox')) ?>
                <?= Studip\LinkButton::create(_("Zeitgesteurte und Teilnahmebeschr�nkte Anmeldung"), $controller->url_for('/instant_course_set', array('type' => 'ParticipantRestrictedAdmission_TimedAdmission')),array('rel' => 'lightbox')) ?>
            <? endif ?> 
            </div>
        <? endif ?>
    </fieldset>
</form>

<? if ($current_courseset && $current_courseset->isSeatDistributionEnabled()) : ?>
    <form class="studip_form" action="<?= $controller->link_for('/change_admission_turnout') ?>" method="post">
    <?= CSRFProtection::tokenTag() ?>
    <fieldset>
        <legend><?= _("Beschr�nkte Teilnehmeranzahl")?></legend>
        <div>
            <?=_("Bitte geben Sie hier an, wieviele Teilnehmer maximal f�r die Veranstaltung vorgesehen sind, und ob eine Warteliste erstellt werden soll, falls die Anmeldungen die maximale Teilnehmeranzahl �berschreiten."); ?>
        </div>
        <label class="caption"><?=_("max. Teilnehmeranzahl:")?></label>
        <label for="admission_turnout">
        <input type="text" name="admission_turnout" id="admission_turnout" style="display:inline" value="<?= $course->admission_turnout ?>" >
        <?= sprintf(_("(%s freie Pl�tze)"), $course->getFreeSeats()) ?></label>
        <label class="caption"><?=_("Warteliste:")?></label>
        <label for="admission_disable_waitlist">
              <input <?=$is_locked['admission_disable_waitlist'] ?> type="checkbox" id="admission_disable_waitlist" name="admission_disable_waitlist" value="1" <?= ($course->admission_disable_waitlist == 0 ? "checked" : ""); ?>>
              <?=_("Warteliste aktivieren")?>
              <? if ($num_waitlist = $course->admission_applicants->findBy('status', 'awaiting')->count() ) : ?>
                  &nbsp;<?= sprintf(_("(%s Wartende)"), $num_waitlist)?>
              <? endif ?>
              </label>
        <label for="admission_disable_waitlist_move">
              <input <?=$is_locked['admission_disable_waitlist_move'] ?> type="checkbox" id="admission_disable_waitlist_move"  name="admission_disable_waitlist_move" value="1" <?= ($course->admission_disable_waitlist_move == 0 ? "checked" : ""); ?>>
              <?=_("automatisches Nachr�cken aus der Warteliste aktivieren")?></label>
        <label for="admission_waitlist_max">
              <input <?=$is_locked['admission_waitlist_max'] ?> type="text" style="display:inline" id="admission_waitlist_max"  name="admission_waitlist_max" value="<?= ($course->admission_waitlist_max ?: '') ?>">
              <?=_("max. Anzahl an Wartenden (optional)")?></label>
        <?= Studip\Button::create(_("Teilnehmeranzahl �ndern"), 'change_admission_turnout', array('rel' => 'lightbox')) ?>
    </fieldset>
    </form>
<? endif ?>

<form class="studip_form" action="<?= $controller->link_for('/change_admission_prelim') ?>" method="post">
<?= CSRFProtection::tokenTag() ?>
    <fieldset>
        <legend><?= _("Anmeldemodus")?></legend>
              <div>
              <?= _("Bitte w�hlen Sie hier einen Anmeldemodus aus:"); ?>
              </div>
              <fieldset>
              <label for="admission_prelim_0">
              <input <?=$is_locked['admission_prelim'] ?> type="radio" id="admission_prelim_0" name="admission_prelim" value="0" <?= ($course->admission_prelim == 0 ? "checked" : ""); ?>>
              <?=_("Direkter Eintrag")?></label>
              <label for="admission_prelim_1">
              <input <?=$is_locked['admission_prelim'] ?> type="radio" id="admission_prelim_1"  name="admission_prelim" value="1" <?= ($course->admission_prelim == 1 ? "checked" : ""); ?>>
              <?=_("Vorl�ufiger Eintrag")?></label>
              </fieldset>
              <? if ($course->admission_prelim == 1) : ?>
                  <label for="admission_prelim_txt" class="caption"><?= _("Hinweistext bei vorl�ufigen Eintragungen:"); ?></label>
                  <textarea <?=$is_locked['admission_prelim_txt'] ?> id="admission_prelim_txt" name="admission_prelim_txt" rows="4"><?
                  echo htmlReady($course->admission_prelim_txt);
                  ?></textarea>
              <? endif ?>
              <label class="caption"><?=_("verbindliche Anmeldung:")?></label>
              <label for="admission_binding">
              <input <?=$is_locked['admission_binding'] ?> id="admission_binding" type="checkbox" <?= ($course->admission_binding == 1 ? "checked" : ""); ?> name="admission_binding"  value="1">
              <?=_("Anmeldung ist <u>verbindlich</u>. (Teilnehmer k�nnen sich nicht austragen.)")?></label>
              <?= Studip\Button::create(_("Anmeldemodus �ndern"), 'change_admission_prelim', array('rel' => 'lightbox')) ?>
    </fieldset>
</form>

<? if (get_config("ENABLE_FREE_ACCESS") && !$current_courseset) : ?>
<form class="studip_form" action="<?= $controller->link_for('/change_free_access') ?>" method="post">
<?= CSRFProtection::tokenTag() ?>
    <fieldset>
        <legend><?= _("freier Zugriff")?></legend>
        <div>
        <?= _("Legen Sie hier fest, ob nicht angemeldete Nutzer Zugriff haben sollen."); ?>
        </div>
        <div style="display: inline-block;padding:1ex;width:50%">
        <label class="caption"><?= _("Lesezugriff") ?></label>
        <label for="lesezugriff">
        <input <?=$is_locked['read_level'] ?> id="lesezugriff" type="checkbox" <?= ($course->lesezugriff == 2 ? "checked" : ""); ?> name="read_level"  value="2">
        <?= _("Lesezugriff f�r nicht angemeldete Nutzer erlauben") ?></label>
        </div>
        <div style="display: inline-block;padding:1ex;">
        <label class="caption"><?= _("Schreibzugriff") ?></label>
        <label for="schreibzugriff">
        <input <?=$is_locked['write_level'] ?> id="schreibzugriff" type="checkbox" <?= ($course->schreibzugriff == 2 ? "checked" : ""); ?> name="write_level"  value="2">
        <?= _("Schreibzugriff f�r nicht angemeldete Nutzer erlauben") ?></label>
        </div>
        <?= Studip\Button::create(_("Freien Zugriff �ndern"), 'change_free_access') ?>
    </fieldset>
    <?  endif ?>
</form>
<? if (count($all_domains)) : ?>
<form class="studip_form" action="<?= $controller->link_for('/change_domains') ?>" method="post">
<?= CSRFProtection::tokenTag() ?>
    <fieldset>
        <legend><?= _("Zugelassenene Nutzerdom�nen")?></legend>
        <div>
            <?=_("Bitte geben Sie hier an, welche Nutzerdom�nen zugelassen sind."); ?>
        </div>
        <fieldset>
        <? foreach ($all_domains as $domain) : ?>
            <label for="user_domain_<?= $domain->getId() ?>">
              <input <?=$is_locked['user_domain'] ?> id="user_domain_<?= $domain->getId() ?>" type="checkbox" <?= (in_array($domain->getId(), $seminar_domains) ? "checked" : ""); ?> name="user_domain[]"  value="<?= $domain->getId() ?>">
              <?= htmlReady($domain->getName())?></label>
        <? endforeach ?>
        </fieldset>
        <?= Studip\Button::create(_("Nutzerdom�nen �ndern"), 'change_domains') ?>
    </fieldset>
</form>
<? endif ?>
