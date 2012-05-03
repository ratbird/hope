<table class="default">
    <thead>
        <tr>
            <td colspan="4" class="topic"><?= $description ?></td>
        </tr>
    </thead>
    <tbody>
    <? if (empty($courses)): ?>
        <tr>
            <td colspan="4" class="printhead" style="text-align: center;">
                <?= _('Es wurde keine Veranstaltung gefunden.') ?>
            </td>
        </tr>
    <? endif; ?>
    <? foreach ($courses as $course): ?>
        <tr>
            <? printhead(0, 0, false, true, false, '&nbsp;', $course['title'], $course['addon'], 0); ?>
        </tr>
    <? endforeach; ?>
    </tbody>
</table>
