<?
# Lifter010: TODO
?>
<? if ($content_for_layout != ''): ?>
    <? if (!isset($admin_title)) $admin_title = _('Administration') ?>

    <table class="default nohover">
        <caption>

        </caption>

        <thead>
            <tr>
                <th colspan="2">
                    <? if (isset($icon_url)): ?>
                        <?= Assets::img($icon_url, array('class' => 'middle')) ?>
                    <? endif ?>
                    <?= htmlReady($title) ?>
                    <? if (isset($admin_url)): ?>
                        <a href="<?= URLHelper::getLink($admin_url) ?>" title="<?= htmlReady($admin_title) ?>">
                            <?= Assets::img('icons/16/blue/admin.png', array('alt' => htmlReady($admin_title))) ?>
                        </a>
                    <? endif ?>
                    <? if (isset($icon_url)): ?>
                        <?= Assets::img($icon_url, array('class' => 'middle')) ?>
                    <? endif ?>
                    <?= htmlReady($head) ?>
                </th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td colspan="2">
                    <?= $content_for_layout ?>
                </td>
            </tr>
        </tbody>
    </table>
    <?

 endif ?>
