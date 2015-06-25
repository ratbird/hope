<? $date_ids = $topic->dates->pluck("termin_id") ?>
<form action="<?= URLHelper::getLink("dispatch.php/course/topics") ?>" method="post">
    <input type="hidden" name="issue_id" value="<?=htmlReady($topic->getId())  ?>">
    <input type="hidden" name="open" value="<?=htmlReady($topic->getId())  ?>">
    <input type="hidden" name="edit" value="1">
    <table style="width: 100%;">
        <tbody>
            <tr>
                <td><strong><label for="topic_title"><?= _("Titel") ?></label></strong></td>
                <td><input type="text" name="title" id="topic_title" value="<?= htmlReady($topic['title']) ?>" style="width: 100%;" required></td>
            </tr>
            <tr>
                <td><strong><label for="topic_description"><?= _("Beschreibung") ?></label></strong></td>
                <td>
                    <textarea class="add_toolbar wysiwyg" name="description" id="topic_description" style="width: 100%; height: 150px;"><?= wysiwygReady($topic['description']) ?></textarea>
                    <? if (Request::isAjax()) : ?>
                    <script>jQuery('.add_toolbar').addToolbar();</script>
                    <? endif ?>
                </td>
            </tr>
            <tr>
                <td><strong><?= _("Termine") ?></strong></td>
                <td>
                    <ul class="clean" style="max-height: 310px; overflow: auto;">
                        <? foreach ($dates as $date) : ?>
                        <li>
                            <label>
                                <?= Assets::img("icons/16/black/date", array('class' => "text-bottom")) ?>
                                <?= (floor($date['date'] / 86400) !== floor($date['end_time'] / 86400)) ? date("d.m.Y, H:i", $date['date'])." - ".date("d.m.Y, H:i", $date['end_time']) : date("d.m.Y, H:i", $date['date'])." - ".date("H:i", $date['end_time']) ?>
                                <input type="checkbox" name="date[<?= $date->getId() ?>]" value="1" class="text-bottom"<?= in_array($date->getId(), $date_ids) ? " checked" : "" ?>>
                            </label>
                            <? $localtopics = $date->topics ?>
                            <? if (count($localtopics)) : ?>
                            (
                                <? foreach ($localtopics as $key => $localtopic) : ?>
                                    <a href="<?= URLHelper:: getLink("dispatch.php/course/topics/index", array('open' => $localtopic->getId())) ?>">
                                        <?= Assets::img("icons/16/blue/topic", array('class' => "text-bottom")) ?>
                                        <?= htmlReady($localtopic['title']) ?>
                                    </a>
                                <? endforeach ?>
                            )
                            <? endif ?>
                        </li>
                        <? endforeach ?>
                    </ul>
                </td>
            </tr>
            <? if ($documents_activated) : ?>
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
            <? endif ?>
            <? if ($forum_activated) : ?>
            <tr>
                <td><strong><?= _("Forumsthema") ?></strong></td>
                <td>
                    <? if ($topic->forum_thread_url) : ?>
                        <?= Assets::img("icons/16/green/accept", array('class' => "text-bottom")) ?>
                        <?= _("Forumsthema vorhanden ") ?>
                    <? else : ?>
                        <label>
                            <input type="checkbox" name="forumthread" id="topic_forumthread">
                            <?= _("Forumsthema anlegen") ?>
                        </label>
                    <? endif ?>
                </td>
            </tr>
            <? endif ?>
        </tbody>
    </table>
    <div align="center" data-dialog-button>
        <div class="button-group">
            <?= \Studip\Button::create(_("speichern")) ?>
            <? if (!$topic->isNew()) : ?>
            <?= \Studip\Button::create(_("löschen"), "delete_topic", array('onClick' => "return window.confirm('"._("Wirklich löschen?")."');")) ?>
            <? endif ?>
        </div>
    </div>
</form>

<br>
