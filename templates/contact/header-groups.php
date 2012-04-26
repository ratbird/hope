<?
    $tooltip = function ($size) {
        if (!$size) {
            $tip = _('Keine Einträge');
        } else {
            $template = $size == 1 ? _('%d Eintrag') : _('%d Einträge');
            $tip = sprintf($template, $size);
        }
        return tooltip($tip, false);
    }
?>
<table class="contact-header">
    <tr>
        <td <? if (($filter ?: 'all') == 'all') echo 'class="active"'; ?>>
            <a href="<?= URLHelper::getLink('?filter=all&view=gruppen') ?>"
                <?= $tooltip($size_of_book) ?>>
                <?= _('Alle Gruppen') ?>
            </a>
            <a href="<?= URLHelper::getLink('?groupid=all')?>">
                <?= Assets::img('icons/16/blue/vcard', tooltip2(_('Alle Einträge als vCard exportieren'))) ?>
            </a>
        </td>
    <? foreach ($groups as $group_id => $name): ?>
        <td class="<? if ($filter == $group_id) echo 'active'; ?><? if (!$sizes[$group_id]) echo ' empty'; ?>">
            <a href="<?= URLHelper::getLink('', compact('view') + array('filter' => $group_id)) ?>"
                <?= $tooltip($sizes[$group_id]) ?>
            >
                <?= htmlReady($name) ?>
            </a>
        <? if ($filter == $group_id): ?>
            <a href="<?= URLHelper::getLink('sms_send.php?sms_source_page=contact.php', compact('group_id')) ?>">
                <?= Assets::img('icons/16/blue/mail', tooltip2(_('Nachricht an alle Personen dieser Gruppe schicken'))) ?>
            </a>
            <a href="<?= URLHelper::getLink('contact_export.php', array('groupid' => $group_id)) ?>">
                <?= Assets::img('icons/16/blue/vcard', tooltip2(_('Diese Gruppe als vCard exportieren'))) ?>
            </a>
        <? endif; ?>
        </td>
    <? endforeach; ?>
    </tr>
</table>
