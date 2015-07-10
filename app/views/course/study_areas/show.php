<form action="<?= $controller->url_for('course/study_areas/save/' . $course->id, $url_params) ?>" method="post">
    <?= $tree ?>
    <div data-dialog-button class="hidden-no-js" style="clear: both; text-align: center">
        <?= Studip\Button::createAccept(_('Speichern')) ?>
    </div>
</form>