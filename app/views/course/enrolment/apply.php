<h1><?= htmlReady($course_name) ?></h1>
<?= $admission_error ?>
<? if ($courseset_message) : ?>
<p>
    <?= $courseset_message ?>
</p>
<? endif ?>
<? foreach(PageLayout::getMessages() as $pm) : ?>
    <?= $pm ?>
<? endforeach; ?>
<? if ($admission_form) : ?>
    <form name="apply_admission" action="<?= $controller->link_for('/apply/' . $course_id) ?>" method="post">
        <?= $admission_form ?>
        <div>
        <?= Studip\Button::create(_("OK"), 'apply', array('rel' => 'lightbox')) ?>
        <?= Studip\Button::create(_("Abbrechen"), 'cancel') ?>
        </div>
        <?= CSRFProtection::tokenTag() ?>
    </form>
<? endif ?>
<? if ($priocourses) : ?>
    <form name="claim_admission" action="<?= $controller->link_for('/claim/' . $course_id) ?>" method="post">
    <? if (is_array($priocourses)): ?>
        <?= $this->render_partial('course/enrolment/_priocourses.php') ?>
    <? else : ?>
        <input type="checkbox" name="courseset_claimed" id="courseset_claimed" value="1" <?= ($already_claimed ? 'checked' : '') ?>>
        <label for="courseset_claimed" style="font-weight:bold"><?=_("Zur Platzverteilung anmelden")?></label>
        &nbsp;(<?= sprintf(_("max. Teilnehmeranzahl: %s / Anzahl der Anmeldungen: %s"), $priocourses->admission_turnout, $num_claiming) ?>)
    <? endif ?>
    <div>
    <?= Studip\Button::create(_("OK"), 'claim', array('rel' => 'lightbox')) ?>
    <?= Studip\Button::create(_("Abbrechen"), 'cancel') ?>
    </div>
    <?= CSRFProtection::tokenTag() ?>
    </form>
<? endif ?>
<? if (!$priocourses && !$admission_form) :?>
    <div>
    <? if ($enrol_user) : ?>
        <?=Studip\LinkButton::create(_('Zur Veranstaltung'), URLHelper::getURL('seminar_main.php', array('auswahl' => $course_id))) ?>
    <? else : ?>
        <?=Studip\LinkButton::create(_('Abbrechen'), URLHelper::getURL('details.php', array('sem_id' => $course_id)), array('rel' => 'close')) ?>
    <? endif ?>
    </div>
<? endif ?>
<script>STUDIP.enrollment();</script>