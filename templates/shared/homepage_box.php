<? if ($content_for_layout != ''): ?>
    <? if (!isset($admin_title)) $admin_title = _('Administration') ?>

    <table class="blank" style="width: 100%;" cellpadding="0" cellspacing="0">
        <tr>
            <td class="topic" style="font-weight: bold;">
                <? if (isset($icon_url)): ?>
                    <?= Assets::img($icon_url, array('class' => 'middle')) ?>
                <? endif ?>
                <?= htmlReady($title) ?>
            </td>

            <td class="topic" style="text-align: right;">
                <? if (isset($admin_url)): ?>
                    <a href="<?= URLHelper::getLink($admin_url) ?>" title="<?= htmlReady($admin_title) ?>">
                        <?= Assets::img('pfeillink.gif', array('alt' => htmlReady($admin_title))) ?>
                    </a>
                <? endif ?>
            </td>
        </tr>

        <tr>
            <td class="index_box_cell" style="padding: 1em 0em;" colspan="2">
                <blockquote>
                    <?= $content_for_layout ?>
                </blockquote>
            </td>
        </tr>
    </table>
<? endif ?>
