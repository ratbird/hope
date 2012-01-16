<?
# Lifter010: TODO
?>
    <!-- Beginn Footer -->
    <div id="layout_footer">
        <ul>
        <? if (Navigation::hasItem('/footer')) : ?>
        <? foreach (Navigation::getItem('/footer') as $nav) : ?>
            <? if ($nav->isVisible()) : ?>
                <li>
                <a
                <? if (is_internal_url($url = $nav->getURL())) : ?>
                    href="<?= URLHelper::getLink($url, $header_template->link_params) ?>"
                <? else : ?>
                    href="<?= htmlspecialchars($url) ?>" target="_blank"
                <? endif ?>
                ><?= htmlReady($nav->getTitle()) ?></a>
                </li>
            <? endif ?>
        <? endforeach ?>
        <? endif ?>
        </ul>
    </div>
    <!-- Ende Footer -->
