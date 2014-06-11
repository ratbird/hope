<? if (count($topics) > 0) : ?>
<table class="default withdetails">
    <thead>
        <tr>
            <th><?= _("Thema") ?></th>
            <th><?= _("Termine") ?></th>
        </tr>
    </thead>
    <tbody>
    <? foreach ($topics as $topic) : ?>
        <tr class="<?= Request::get("open") === $topic->getId() ? "open" : "" ?>">
            <td><a href="" onClick="jQuery(this).closest('tr').toggleClass('open'); return false;"><?= htmlReady($topic['title']) ?></a></td>
            <td>
                <ul class="clean">
                    <? foreach ($topic->dates as $date) : ?>
                        <li>
                            <a href="<?= URLHelper::getLink("dispatch.php/course/dates/details/".$date->getId()) ?>" data-dialog="buttons=false">
                                <?= Assets::img("icons/16/blue/date", array('class' => "text-bottom")) ?>
                                <?= htmlReady($date->getFullName()) ?>
                            </a>
                        </li>
                    <? endforeach ?>
                </ul>
            </td>
        </tr>
        <tr class="details nohover">
            <td colspan="2">
                <div class="detailscontainer">
                    <table class="default nohover">
                        <tbody>
                        <tr>
                            <td><strong><?= _("Beschreibung") ?></strong></td>
                            <td><?= formatReady($topic['description']) ?></td>
                        </tr>
                        <tr>
                            <td><strong><?= _("Materialien") ?></strong></td>
                            <td>
                                <? $material = false ?>
                                <ul class="clean">
                                    <? $folder = $topic->folder ?>
                                    <? if ($folder) : ?>
                                        <li>
                                            <a href="<?= URLHelper::getLink("folder.php#anker", array('data[cmd]' => "tree", 'open' => $folder->getId())) ?>">
                                                <?= Assets::img("icons/16/blue/folder-empty", array('class' => "text-bottom")) ?>
                                                <?= _("Dateiordner") ?>
                                            </a>
                                        </li>
                                        <? $material = true ?>
                                    <? endif ?>

                                    <? if (class_exists("ForumIssue")) : ?>
                                        <? $posting = ForumEntry::getEntry(ForumIssue::getThreadIdForIssue($topic->getId())) ?>
                                        <? if ($posting) : ?>
                                            <li>
                                                <a href="<?= URLhelper::getLink("plugins.php/coreforum/index/index/".$posting['topic_id']."#".$posting['topic_id']) ?>">
                                                    <?= Assets::img("icons/16/blue/forum", array('class' => "text-bottom")) ?>
                                                    <?= _("Thema im Forum") ?>
                                                </a>
                                            </li>
                                            <? $material = true ?>
                                        <? endif ?>
                                    <? endif ?>
                                </ul>
                                <? if (!$material) : ?>
                                    <?= _("Keine Materialien zu dem Thema vorhanden") ?>
                                <? endif ?>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                    <div style="text-align: center;">
                        <? if ($GLOBALS['perm']->have_studip_perm("tutor", $_SESSION['SessionSeminar'])) : ?>
                            <form action="?" method="post" style="display: inline;">
                                <input type="hidden" name="move_down" value="<?= $topic->getId() ?>">
                                <input type="hidden" name="open" value="<?= $topic->getId() ?>">
                                <?= \Studip\Button::create(_("nach unten verschieben")) ?>
                            </form>
                            <form action="?" method="post" style="display: inline;">
                                <input type="hidden" name="move_up" value="<?= $topic->getId() ?>">
                                <input type="hidden" name="open" value="<?= $topic->getId() ?>">
                                <?= \Studip\Button::create(_("nach oben verschieben")) ?>
                            </form>
                            <a href="<?= URLHelper::getLink("dispatch.php/course/topics/edit/".$topic->getId()) ?>" data-dialog>
                                <?= \Studip\Button::create(_("bearbeiten"), null, array()) ?>
                            </a>
                        <? endif ?>
                    </div>
                </div>
            </td>
        </tr>
    <? endforeach ?>
    </tbody>
</table>
<? else : ?>
    <? PageLayout::postMessage(MessageBox::info(_("Keine Themen vorhanden."))) ?>
<? endif ?>

<?php
$sidebar = Sidebar::get();
$sidebar->setImage(Assets::image_path("sidebar/date-sidebar.png"));

$actions = new ActionsWidget();
$actions->addLink(_("Alle Themen aufklappen"), null, null, array('onClick' => "jQuery('table.withdetails > tbody > tr:not(.details):not(.open) > :first-child a').click(); return false;"));
if ($GLOBALS['perm']->have_studip_perm("tutor", $_SESSION['SessionSeminar'])) {
    $actions->addLink(_("Neues Thema erstellen"), URLHelper::getURL("dispatch.php/course/topics/edit"), null, array('data-dialog' => "buttons"));
}
$sidebar->addWidget($actions);

