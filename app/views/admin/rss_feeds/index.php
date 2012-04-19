<? 
# Lifter010: TEST

use Studip\Button, Studip\LinkButton;
?>
<a id="rss-feeds"></a>

<p>
    <?= _('Hier können Sie beliebige eigene RSS-Feeds einbinden. '
         .'Diese RSS-Feeds erscheinen auf Ihrer persönlichen Startseite. '
         .'Mit den Pfeilsymbolen können Sie die Reihenfolge, in der die '
         .'RSS-Feeds angezeigt werden, verändern.') ?>
</p>
<p>
    <?= _('<b>Achtung:</b> Je mehr RSS-Feeds Sie definieren, desto länger '
         .'ist die Ladezeit der Startseite für Sie!') ?>
</p>

<form action="<?= $controller->url_for('admin/rss_feeds/update') ?>" method="post">
    <?= CSRFProtection::tokenTag() ?>
    <table class="default">
        <colgroup>
            <col width="50px">
            <col width="50%">
            <col>
            <col width="70px">
        </colgroup>
        <tbody>
        <? if (empty($feeds)): ?>
            <tr class="<?= TextHelper::cycle('steelgraulight', 'steel1') ?>">
                <td colspan="4" style="font-weight: bold;">
                    <?= _('Es existieren zur Zeit keine eigenen RSS-Feeds.') ?>
                </td>
            </tr>
        <? endif; ?>
        <? foreach ($feeds as $index => $feed): ?>
            <tr class="<?= $cycle = TextHelper::cycle('steelgraulight', 'steel1') ?>">
                <td>
                    <input type="hidden" name="feeds[<?= $index ?>][id]" value="<?= $feed->id ?>">
                    <label for="rss_name_<?= $index ?>"><?= _('Name:') ?></label>
                </td>
                <td>
                    <input type="text" name="feeds[<?= $index ?>][name]" id="rss_name_<?= $index ?>" style="width: 100%" value="<?= htmlReady($feed->name) ?>">
                </td>
                <td>
                    <label>
                        <input type="checkbox" name="feeds[<?= $index ?>][fetch_title]" value="1"
                               <? if ($feed->fetch_title) echo 'checked'; ?>>
                        <?= _('Namen des Feeds holen') ?>
                    </label>
                </td>
                <td rowspan="2">
                <? if ($index): ?>
                    <a href="<?= $controller->url_for('admin/rss_feeds/move/' . $feed->id. '/up') ?>">
                        <?= Assets::img('icons/16/yellow/arr_2up.png',
                                        tooltip2(_('RSS-Feed nach oben verschieben'))) ?>
                    </a>
                <? else: ?>
                    <?= Assets::img('icons/16/grey/arr_2up.png') ?>
                <? endif; ?>
                <? if ($index != count($feeds) - 1): ?>
                    <a href="<?= $controller->url_for('admin/rss_feeds/move/' . $feed->id. '/down') ?>">
                        <?= Assets::img('icons/16/yellow/arr_2down.png',
                                        tooltip2(_('RSS-Feed nach unten verschieben'))) ?>
                    </a>
                <? else: ?>
                    <?= Assets::img('icons/16/grey/arr_2down.png') ?>
                <? endif; ?>
                    <a href="<?= $controller->url_for('admin/rss_feeds/delete/' . $feed->id) ?>">
                        <?= Assets::img('icons/16/blue/trash', tooltip2(_('Löschen'))) ?>
                    </a>
                </td>
            </tr>
            <tr class="<?= $cycle ?>">
                <td>
                    <label for="rss_url_<?= $index ?>"><?= _('URL:') ?></label>
                </td>
                <td>
                    <input type="url" id="rss_url_<?= $index ?>" name="feeds[<?= $index ?>][url]" style="width: 100%" value="<?= htmlReady($feed->url) ?>">
                </td>
                <td>
                    <label>
                        <input type="checkbox" name="feeds[<?= $index ?>][active]" value="1"
                               <? if (!$feed->hidden) echo 'checked'; ?>>
                        <?= _('Aktiv') ?>
                    </label>
                </td>
                </td>
            </tr>
        <? endforeach; ?>
        </tbody>
    <? if (!empty($feeds)): ?>
        <tfoot>
            <tr>
                <td colspan="4" style="text-align: center;">
                    <?= Button::createAccept(_('Übernehmen'), array('title' => _('verändern'))) ?>
                </td>
            </tr>
        </tfoot>
    <? endif; ?>
    </table>
</form>
