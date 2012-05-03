<table class="default">
    <tr>
        <td class="topic" colspan="3" style="font-weight: bold">
            <?= _('Informationen zu einem Nutzer:') ?>

            <?= htmlReady($user->getFullName()) ?>
            (<?= $user->perms ?>)
        </td>
    </tr>
<? foreach ($queries as $query): ?>
    <tr class="<?= TextHelper::cycle('cycle_even', 'cycle_odd') ?>">
        <td style="font-weight: bold;"><?= $query['desc'] ?></td>
        <td <? if (!$query['value']) echo 'style="color:#888;"'; ?>>
            <?= htmlReady($query['value']) ?>
        </td>
        <td width="1%">
        <? if ($query['details']): ?>
            <a href="<?= URLHelper::getLink('?' . $query['details']) ?>">
                <?= Assets::img('icons/16/blue/edit', tooltip2(_('Bearbeiten'))) ?>
            </a>
        <? endif; ?>
        </td>
    </tr>
<? endforeach; ?>
</table>

<? if ($details): ?>
    <br>
    <?= $details ?>
<? endif; ?>
