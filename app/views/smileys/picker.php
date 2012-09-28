<div class="smiley-picker">
    <table class="navigation top">
        <tr>
        <? if ($favorites_activated && count($favorites->get()) > 0): ?>
            <td>
                <a href="<?= $controller->url_for('smileys/picker/favorites') ?>">
                    <?= Assets::img('icons/16/' . ($view === 'favorites' ? 'red' : 'blue') . '/star', tooltip2(_('Favoriten'))) ?>
                </a>
            </td>
        <? endif; ?>
            <td style="text-align: right;">
                <a href="<?= $controller->url_for('smileys/picker/all') ?>">
                    <?= Assets::img('icons/16/' . ($view === 'all' ? 'red' : 'blue') . '/smiley', tooltip2(_('alle'))) ?>
                </a>
            </td>
        <? for ($i = 0; $i < 26; $i++):
               $char = chr(ord('a') + $i);
        ?>
            <td <? if ($view === $char) echo 'class="active"'; ?>>
            <? if (isset($characters[$char])): ?>
                <a href="<?= $controller->url_for('smileys/picker/'. $char) ?>">
                    <?= strtoupper($char) ?>
                </a>
            <? else: ?>
                <?= $char ?>
            <? endif; ?>
            </td>
        <? endfor; ?>
        </tr>
    </table>

    <div class="smileys">
<? foreach (array_pad($smileys, $controller::GRID_WIDTH * $controller::GRID_HEIGHT, null) as $smiley): ?>
    <? if ($smiley === null): ?>
        <span class="empty"></span>
    <? else: ?>
        <a class="smiley" href="#" data-code="<?= $smiley->short ?: (':' . $smiley->name . ':') ?>">
            <?= $smiley->html ?>
        </a>
    <? endif; ?>
<? endforeach; ?>
    </div>

    <table class="navigation bottom">
        <tr>
            <td>
            <? if ($page > 0): ?>
                <a href="<?= $controller->url_for('smileys/picker/' . $view . '/0') ?>">
                    <?= Assets::img('icons/16/blue/arr_eol-left') ?>
                </a>
                <a href="<?= $controller->url_for('smileys/picker/' . $view . '/' . ($page - 1)) ?>">
                    <?= Assets::img('icons/16/blue/arr_1left') ?>
                </a>
            <? else: ?>
                <?= Assets::img('icons/16/grey/arr_eol-left') ?>
                <?= Assets::img('icons/16/grey/arr_1left') ?>
            <? endif; ?>
            </td>
            <td style="text-align: center;">
                <?= sprintf('Seite %u von %u', $page + 1, $pages + 1) ?>
            </td>
            <td style="text-align: right;">
            <? if ($page < $pages): ?>
                <a href="<?= $controller->url_for('smileys/picker/' . $view . '/' . ($page + 1)) ?>">
                    <?= Assets::img('icons/16/blue/arr_1right') ?>
                </a>
                <a href="<?= $controller->url_for('smileys/picker/' . $view . '/' . $pages) ?>">
                    <?= Assets::img('icons/16/blue/arr_eol-right') ?>
                </a>
            <? else: ?>
                <?= Assets::img('icons/16/grey/arr_1right') ?>
                <?= Assets::img('icons/16/grey/arr_eol-right') ?>
            <? endif; ?>
            </td>
        </tr>
    </table>
</div>