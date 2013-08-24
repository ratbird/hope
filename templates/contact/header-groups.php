<?
    $tooltip = function ($size) {
        if (!$size) {
            $tip = _('Keine Eintr�ge');
        } else {
            $template = $size == 1 ? _('%d Eintrag') : _('%d Eintr�ge');
            $tip = sprintf($template, $size);
        }
        return tooltip($tip, false);
    }
?>
<table class="contact-header" width="70%">
    <tr>
        <td nobreak <? if (($filter ?: 'all') == 'all') echo 'class="active"'; ?>>
            <a href="<?= URLHelper::getLink('?filter=all&view=gruppen') ?>"
                <?= $tooltip($size_of_book) ?>>
                <?= _('Alle Gruppen') ?>
            </a>
            <a href="<?= URLHelper::getLink('?groupid=all')?>">
            </a>
        </td>
    <? foreach ($groups as $group_id => $name): ?>
        <td nobreak class="<? if ($filter == $group_id) echo 'active'; ?><? if (!$sizes[$group_id]) echo ' empty'; ?>">
            <a href="<?= URLHelper::getLink('', compact('view') + array('filter' => $group_id)) ?>"
                <?= $tooltip($sizes[$group_id]) ?>
            >
                <?= htmlReady($name) ?>
            </a>
        <? if ($filter == $group_id): ?>
            <a href="<?= URLHelper::getLink('sms_send.php?sms_source_page=contact.php', compact('group_id')) ?>">
                <?= Assets::img('icons/16/blue/mail', tooltip2(_('Nachricht an alle Personen dieser Gruppe schicken'))) ?>
            </a>
        <? endif; ?>
        </td>
    <? endforeach; ?>
    </tr>
</table>
