<form action="<?= URLHelper::getLink("dispatch.php/course/topics/copy") ?>" method="post">
    <script>
        STUDIP.Topics = {
            loadTopics: function () {
                jQuery.ajax({
                    'url': STUDIP.ABSOLUTE_URI_STUDIP + "dispatch.php/course/topics/fetch_topics",
                    'data': { 'seminar_id': jQuery("input[name=copy_from]").val() },
                    'dataType': "json",
                    'success': function (json) {
                        jQuery("#topiclist").html(json.html);
                    }
                })
            }
        };
    </script>
    <div style="text-align: center;">
        <?= Assets::img("icons/20/blue/seminar", array('class' => "text-bottom")) ?>
        <?= QuickSearch::get("copy_from", $courseSearch)
            ->fireJSFunctionOnSelect("STUDIP.Topics.loadTopics")
            ->render() ?>
    </div>
    <div id="topiclist" style="min-height: 50px; text-align: center;">
        <? if (Request::option("seminar_id")) : ?>
        <?= $this->render_partial("_topiclist.php", array('topics' => CourseTopics::findBySeminar_id(Request::option("seminar_id")))) ?>
        <? endif ?>
    </div>
    <div align="center" data-dialog-button>
        <?= \Studip\Button::create(_("kopieren"), 'copy') ?>
    </div>

</form>