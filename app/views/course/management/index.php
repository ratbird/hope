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

<div>
    <div style="margin-left: 1.5em;">

        <? foreach (Navigation::getItem('/course/admin') as $name => $nav) : ?>
            <? if ($nav->isVisible() && $name != 'main') : ?>
                <a class="click_me" href="<?= URLHelper::getLink($nav->getURL()) ?>">
                    <div>
                        <span class="click_head">
                            <?= htmlReady($nav->getTitle()) ?>
                        </span>
                        <p>
                            <?= htmlReady($nav->getDescription()) ?>
                        </p>
                    </div>
                </a>
            <? endif ?>
        <? endforeach ?>

    </div>
    <br style="clear: left;">
</div>
