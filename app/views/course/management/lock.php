<?php
# Lifter010: TODO
/* * * * * * * * * * * * *
 * * * I N F O B O X * * *
 * * * * * * * * * * * * */

$sidebar = Sidebar::get();
$sidebar->setImage('sidebar/admin-sidebar.png');

if (Course::findCurrent()) {
    $links = new ActionsWidget();
    foreach (Navigation::getItem('/course/admin/main') as $nav) {
        if ($nav->isVisible(true)) {
            $image = $nav->getImage();
            $links->addLink($nav->getTitle(), URLHelper::getLink($nav->getURL(), array('studip_ticket' => Seminar_Session::get_ticket())), $image['src']);
        }
    }
    $sidebar->addWidget($links);
    // Entry list for admin upwards.
    if ($GLOBALS['perm']->have_studip_perm("admin", $GLOBALS['SessionSeminar'])) {
        $list = new SelectorWidget();
        $list->setUrl("?#admin_top_links");
        $list->setSelectParameterName("cid");
        foreach (AdminCourseFilter::get()->getCoursesForAdminWidget() as $seminar) {
            $list->addElement(new SelectElement($seminar['Seminar_id'], $seminar['Name']), 'select-' . $seminar['Seminar_id']);
        }
        $list->setSelection($this->course_id);
        $sidebar->addWidget($list);
    }
}
?>
<h1><?= PageLayout::getTitle() ?></h1>
<form action="<?= $controller->url_for('course/management/set_lock_rule') ?>" method="post" class="studip-form">
    <?= CSRFProtection::tokenTag() ?>
    <section>
        <select name="lock_sem" id="lock_sem" aria-labelledby="<?= _('Sperrebene auswählen')?>">
            <? foreach ($all_lock_rules as $lock_rule) : ?>
                <option
                    value="<?= $lock_rule['lock_id'] ?>" <?= $current_lock_rule->id == $lock_rule['lock_id'] ? 'selected' : '' ?>>
                    <?= htmlReady($lock_rule['name']) ?>
                </option>
            <? endforeach ?>
        </select>
    </section>
    <footer>
        <?= Studip\Button::createAccept(_('Speichern')) ?>
    </footer>
</form>