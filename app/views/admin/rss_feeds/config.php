<?
# Lifter010: TEST

use Studip\Button;
?>
<form action="<?= $controller->url_for('admin/rss_feeds/config') ?>" method="post" onchange="this.submit()">
    <label>
        <?= _('Pro Feed angezeigte Einträge:') ?>
        <select name="limit">
            <option value="-1" <? if ($limit == -1) echo 'selected'; ?>>
                <?= _('Alle') ?>
            </option>
        <? for ($i = 1; $i <= 25; $i++): ?>
            <option <? if ($limit == $i) echo 'selected'; ?>>
                <?= $i ?>
            </option>
        <? endfor; ?>
        </select>
    </label>
    <noscript>
        <?= Button::create(_('Speichern'), 'store') ?>
    </noscript>
</form>