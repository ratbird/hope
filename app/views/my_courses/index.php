<? if (sizeof($waiting_list)) : ?>
    <?= $this->render_partial('my_courses/waiting_list.php', compact('waiting_list')) ?>
<? endif ?>


<? if (isset($flash['decline_course'])) : ?>
    <?=
    createQuestion($flash['message'], array('cmd' => $flash['cmd'], 'studipticket' => $flash['studipticket']),
        array('cmd'          => 'back',
              'studipticket' => $flash['studipticket']),
        $controller->url_for(sprintf('my_courses/decline/%s', $flash['course_id']))); ?>
<? endif ?>

<? if (!empty($sem_courses)) : ?>
    <? SkipLinks::addIndex(_("Meine Veranstaltungen"), 'my_seminars') ?>
    <div id="my_seminars">
        <? foreach ($sem_courses as $sem_key => $course_group) : ?>
            <table class="default collapsable">
                <caption>
                    <?= sprintf(_("Meine Veranstaltungen im %s"), htmlReady($sem_data[$sem_key]['name'])) ?>
                </caption>
                <colgroup>
                    <col width="10px">
                    <col width="25px">
                    <col>
                    <col width="35%">
                    <col width="3%">
                </colgroup>
                <thead>
                <tr class="sortable">
                    <th colspan="3"class=<?=
                    ($order_by == "name")
                        ? ($order == 'desc')
                            ? 'sortdesc'
                            : 'sortasc'
                        : '' ?>>
                        <? $_order = (!$order_by || $order == 'desc') ? 'asc' : 'desc' ?>
                        <a href="<?= $controller->url_for(sprintf('my_courses/index/name/%s', $_order)) ?>">
                            <?= _("Name") ?>
                        </a>
                    </th>
                    <th><?= _("Inhalt") ?></th>
                    <th></th>
                </tr>
                </thead>
                <? if (strcmp($group_field, 'sem_number') !== 0) : ?>
                    <?= $this->render_partial("my_courses/_group", compact('course_group')) ?>
                <? else : ?>
                    <? $course_collection = $course_group ?>
                    <?= $this->render_partial("my_courses/_course", compact('course_collection')) ?>
                <? endif ?>
            </table>
        <? endforeach ?>
    </div>
<? else : ?>
    <?=
    PageLayout::postMessage(MessageBox::info(sprintf(_("Sie haben zur Zeit keine Veranstaltungen abonniert, an denen Sie teilnehmen k&ouml;nnen.
    Bitte nutzen Sie %s<b>Veranstaltung suchen / hinzuf&uuml;gen</b>%s um neue Veranstaltungen aufzunehmen oder w�hlen Sie ein anderes Semester aus."),
        "<a href=\"sem_portal.php\">", "</a>")))?>
<? endif ?>

<? if (count($my_bosses)) : ?>
    <?= $this->render_partial('my_courses/_deputy_bosses'); ?>
<? endif ?>
