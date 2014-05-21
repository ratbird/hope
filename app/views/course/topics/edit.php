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
                    <textarea class="add_toolbar" name="description" id="topic_description" style="width: 100%;"><?= htmlReady($topic['description']) ?></textarea>
                </td>
            </tr>
        </tbody>
    </table>
    <div style="text-align: center;">
        <?= \Studip\Button::create(_("speichern")) ?>
    </div>
</form>