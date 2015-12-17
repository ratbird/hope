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
            $links->addLink($nav->getTitle(), URLHelper::getLink($nav->getURL(), array('studip_ticket' => Seminar_Session::get_ticket())), $nav->getImage(), $nav->getLinkAttributes());
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

<ul class="boxed-grid">
<? foreach (Navigation::getItem('/course/admin') as $name => $nav): ?>
    <? if ($nav->isVisible() && $name != 'main'): ?>
        <li>
            <a href="<?= URLHelper::getLink($nav->getURL()) ?>">
                <h3>
                    <? if ($nav->getImage()): ?>
                        <?= $nav->getImage()->asImg(false, $nav->getLinkAttributes()) ?>
                    <? endif; ?>
                    <?= htmlReady($nav->getTitle()) ?>
                </h3>
                <p>
                    <?= htmlReady($nav->getDescription()) ?>
                </p>
            </a>
        </li>
    <? endif; ?>
<? endforeach; ?>
<!--
    this is pretty ugly but we need to spawn some empty elements so that the
    last row of the flex grid won't be messed up if the boxes don't line up
-->
    <li></li><li></li><li></li>
    <li></li><li></li><li></li>
</ul>