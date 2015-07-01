<form action="<?= $url ?>" method="post">
    <select id="course_type" name="course_type" style="width: 100%">
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
    <script>
        jQuery('#course_type').live('change', function() {
            jQuery(this).closest('form').submit();
        })
    </script>
    <noscript><?= \Studip\Button::createAccept(_('Absenden')); ?></noscript>
</form>