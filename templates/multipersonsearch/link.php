<a href="<?= URLHelper::getLink('dispatch.php/multipersonsearch/no_js_form/?name=' . $name); ?>" class="multi_person_search_link" data-lightbox="width=720;height=460"  data-dialogname="<?= $name; ?>" title="<?=$title;?>" data-js-form="<?= URLHelper::getLink('dispatch.php/multipersonsearch/js_form/' . $name); ?>">
    <?
    if (!empty($linkIconPath)) {
        print Assets::img($linkIconPath, tooltip2(_('Personen hinzufügen')));
    }
    if (!empty($linkIconPath) && !empty($linkText)) {
        print " ";
    }
    if (!empty($linkText)) {
        print $linkText;
    }
    ?>
</a>
