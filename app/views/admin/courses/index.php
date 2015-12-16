<? if (empty($insts)): ?>
    <?= MessageBox::info(sprintf(_('Sie wurden noch keinen Einrichtungen zugeordnet. Bitte wenden Sie sich an einen der zust�ndigen %sAdministratoren%s.'), '<a href="' . URLHelper::getLink('dispatch.php/siteinfo/show') . '">', '</a>')) ?>
<? elseif (!empty($courses)): ?>
    <?= $this->render_partial('admin/courses/courses.php', compact('courses')) ?>
<? elseif ($count_courses): ?>
    <?= MessageBox::info(sprintf(_('Es wurden %u Veranstaltungen gefunden. Grenzen Sie diese mit den Filterm�glichkeiten weiter ein.'), $count_courses)) ?>
<? else: ?>
    <?= MessageBox::info(_('Ihre Suche ergab keine Treffer')) ?>
<? endif; ?>
