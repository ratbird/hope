<form action="<?= $url ?>" method="post">
    <select id="course_type" name="course_type" style="width: 100%" onchange="jQuery(this).closest('form').submit();">
        <option value="all" <?= ($selected == 'all' ? 'selected="selected"' : '') ?>><?= _('Alle') ?></option>
        <? foreach ($GLOBALS['SEM_CLASS'] as $class_id => $class) : ?>
            <? if (!$class['studygroup_mode']) : ?>
                <option style="font-weight:bold" value="<?=$class_id?>" <?= ($selected == $class_id ? 'selected="selected"' : '')?>><?= htmlReady($class['name'])?></option>
                    <? foreach ($class->getSemTypes() as $id => $result) : ?>
                        <option value="<?=$class_id . '_' . $id?>" <?= ($selected == $class_id . '_' . $id ? 'selected="selected"' : '')?>>
                            &nbsp;&nbsp;<?= htmlReady($result['name'])?>
                        </option>
                <? endforeach ?>
            <? endif ?>
        <? endforeach ?>
    </select>
    <noscript><?= \Studip\Button::createAccept(_('Absenden')); ?></noscript>
</form>