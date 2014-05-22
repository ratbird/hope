<form action="<?= URLHelper::getLink("dispatch.php/course/topics") ?>" method="post">
    <input type="hidden" name="issue_id" value="<?=htmlReady($topic->getId())  ?>">
    <table style="width: 100%;">
        <tbody>
            <tr>
                <td><strong><label for="topic_title"><?= _("Titel") ?></label></strong></td>
                <td><input type="text" name="title" id="topic_title" value="<?= htmlReady($topic['title']) ?>" style="width: 100%;"></td>
            </tr>
            <tr>
                <td><strong><label for="topic_description"><?= _("Beschreibung") ?></label></strong></td>
                <td>
                    <textarea class="add_toolbar" name="description" id="topic_description" style="width: 100%; height: 150px;"><?= htmlReady($topic['description']) ?></textarea>
                    <? if (Request::isAjax()) : ?>
                    <script>jQuery(function() { STUDIP.Toolbar.initialize(jQuery("#topic_description")[0]); });</script>
                    <? endif ?>
                </td>
            </tr>
            <tr>
                <td><strong><?= _("Themen-Dateiordner") ?></strong></td>
                <td>
                    <? $folder = $topic->folder ?>
                    <? if ($folder) : ?>
                        <?= Assets::img("icons/16/green/accept", array('class' => "text-bottom")) ?>
                        <?= _("Dateiordner vorhanden ") ?>
                    <? else : ?>
                        <label>
                            <input type="checkbox" name="folder" id="topic_folder">
                            <?= _("Dateiordner anlegen") ?>
                        </label>
                    <? endif ?>
                </td>
            </tr>
            <tr>
                <td><strong><?= _("Forumsthema") ?></strong></td>
                <td>
                    <? if (class_exists("ForumIssue")) : ?>
                        <? $posting = ForumEntry::getEntry(ForumIssue::getThreadIdForIssue($topic->getId())) ?>
                        <? if ($posting) : ?>
                            <?= Assets::img("icons/16/green/accept", array('class' => "text-bottom")) ?>
                            <?= _("Forumsthema vorhanden ") ?>
                        <? else : ?>
                            <label>
                                <input type="checkbox" name="forumthread" id="topic_forumthread">
                                <?= _("Forumsthema anlegen") ?>
                            </label>
                        <? endif ?>
                    <? endif ?>
                </td>
            </tr>
        </tbody>
    </table>
    <div style="text-align: center;">
        <?= \Studip\Button::create(_("speichern")) ?>
    </div>
</form>