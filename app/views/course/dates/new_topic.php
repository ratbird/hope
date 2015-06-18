<form action="<?= URLHelper::getLink("dispatch.php/course/dates") ?>" method="post" id="dates_add_topic">
    <input type="hidden" name="termin_id" value="<?= $date->getId() ?>">
    <table class="default">
        <tbody>
        <tr>
            <td><?= _("Termin") ?></td>
            <td class="date_name"><?= htmlReady($date->getFullname()) ?></td>
        </tr>
        <tr>
            <td><?= _("Thema") ?></td>
            <td>
                <input type="text" class="topic_title" name="topic_title" required>
                <script>
                    jQuery(function () {
                        jQuery("#dates_add_topic .topic_title").autocomplete({
                            'source': <?= json_encode(studip_utf8encode($course->topics->pluck('title'))) ?>,
                            'select': function () {
                                jQuery("form#dates_add_topic").submit();
                            }
                        });
                    });
                </script>
            </td>
        </tr>
        <tr>
            <td><?= _("Vorhandenes Thema verknüpfen") ?></td>
            <td>
                <ul class="clean">
                <? foreach ($course->topics as $topic) : ?>
                    <li>
                        <a href="#" onClick="jQuery('#dates_add_topic .topic_title').val('<?= htmlReady($topic['title']) ?>'); jQuery('#dates_add_topic').submit(); return false;">
                            <?= Assets::img("icons/blue/16/arr_2up") ?>
                            <?= htmlReady($topic['title']) ?>
                        </a>
                    </li>
                <? endforeach ?>
                </ul>
            </td>
        </tr>
        </tbody>
    </table>
    <div data-dialog-button>
        <?= \Studip\Button::create(_("Hinzufügen")) ?>
    </div>
</form>