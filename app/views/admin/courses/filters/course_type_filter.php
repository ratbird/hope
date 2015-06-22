<form action="<?= $url ?>" method="post">
    <select id="course_type" name="course_type" style="width: 100%">
        <option value="all" <?= ($selected == 'all' ? 'selected="selected"' : '') ?>><?= _('Alle') ?></option>
        <? foreach ($GLOBALS['SEM_CLASS'] as $class_id => $class) : ?>
            <optgroup label="<?= htmlReady($class['name'])?>">
                <? foreach ($GLOBALS['SEM_TYPE'] as $id => $result) : ?>
                    <option value="<?=$id?>" <?= ($selected == $id ? 'selected="selected"' : '')?>>
                        <?= htmlReady($result['name'])?>
                    </option>
            <? endforeach ?>
            </optgroup>
        <? endforeach ?>
    </select>
    <script>
        jQuery('#course_type').live('change', function() {
            jQuery(this).closest('form').submit();
        })
    </script>
    <noscript><?= \Studip\Button::createAccept(_('Absenden')); ?></noscript>
</form>