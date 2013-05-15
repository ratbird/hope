<?
# Lifter010: TODO
?>
    <!-- Beginn Footer -->
    <div id="layout_footer">
        <? if (is_object($GLOBALS['user']) && $GLOBALS['user']->id != 'nobody') : ?>
            <div id="footer" style="float: left; margin: 4px; color: #dfdfdf;">
                <? printf(_('Sie sind angemeldet als %s (%s)'),
                          $GLOBALS['auth']->auth['uname'],
                          $GLOBALS['auth']->auth['perm']) ?>
                |
                <?= date("d.m.Y, H:i:s", time()) ?>
            </div>
        <? endif ?>
        <ul>
        <? if (Navigation::hasItem('/footer')) : ?>
        <? foreach (Navigation::getItem('/footer') as $nav) : ?>
            <? if ($nav->isVisible()) : ?>
                <li>
                <a
                <? if (is_internal_url($url = $nav->getURL())) : ?>
                    href="<?= URLHelper::getLink($url, $header_template->link_params) ?>"
                <? else : ?>
                    href="<?= htmlReady($url) ?>" target="_blank"
                <? endif ?>
                ><?= htmlReady($nav->getTitle()) ?></a>
                </li>
            <? endif ?>
        <? endforeach ?>
        <? endif ?>
        </ul>
    </div>
    <!-- Ende Footer -->
