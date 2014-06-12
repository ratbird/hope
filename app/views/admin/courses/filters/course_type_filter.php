<form action="<?= $url ?>" method="post">
    <select id="course_type" size="6" name="course_type" style="width: 100%">
        <option value="all" <?= ($selected == 'all' ? 'selected="selected"' : '') ?>><?= _('Alle') ?></option>
        <? foreach ($types as $cat => $ids) : ?>
            <optgroup label="<?= htmlReady($cat)?>">
                <? foreach ($ids as $id => $result) : ?>
                    <option value="<?=$id?>" <?= ($selected == $id ? 'selected="selected"' : '')?>>
                        <?= htmlReady($result['name'])?> (<?= htmlReady($result['amount'])?>)
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