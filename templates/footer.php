<!-- Beginn Footer -->
<div id="layout_footer">
<? if (is_object($GLOBALS['user']) && $GLOBALS['user']->id != 'nobody') : ?>
    <div id="footer">
        <? printf(_('Sie sind angemeldet als %s (%s)'),
                  htmlReady($GLOBALS['user']->username),
                  htmlReady($GLOBALS['user']->perms)) ?>
        |
        <?= strftime('%x, %X') ?>
    <? if (Studip\ENV === 'development'): ?>
        [<?= sprintf('%u db queries', DBManager::get()->query_count) ?>]
    <? endif; ?>
    </div>
<? endif; ?>

<? if (Navigation::hasItem('/footer')) : ?>
    <ul>
    <? foreach (Navigation::getItem('/footer') as $nav): ?>
        <? if ($nav->isVisible()): ?>
            <li>
            <a
            <? if (is_internal_url($url = $nav->getURL())) : ?>
                href="<?= URLHelper::getLink($url, $header_template->link_params) ?>"
            <? else: ?>
                href="<?= htmlReady($url) ?>" target="_blank"
            <? endif ?>
            ><?= htmlReady($nav->getTitle()) ?></a>
            </li>
        <? endif; ?>
    <? endforeach; ?>
    </ul>
<? endif; ?>
</div>
<script>
STUDIP.Navigation = <?= json_encode(studip_utf8encode(ResponsiveHelper::getNavigationArray())) ?>;
</script>
<!-- Ende Footer -->
