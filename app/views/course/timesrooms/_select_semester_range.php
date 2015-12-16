<form action="<?= $controller->url_for('course/timesrooms/index', array('cmd' => 'applyFilter'))?>" method="post" class="default" data-dialog="size=big">
    <section>
        <label class="undecorated">
            <?= _('Semester auswählen') ?>
            <select name="newFilter" class="size-m">
                <? foreach ($selection as $item) : ?>
                    <option value="<?= $item['value']?>" <?= $item['is_selected'] ? 'selected' : ''?>><?= htmlReady($item['linktext'])?></option>
                <? endforeach ?>
            </select>
        </label>

        <?= Studip\Button::createAccept(_('Auswählen'), 'select_sem')?>
    </section>
</form>