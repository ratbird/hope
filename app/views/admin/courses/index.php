<? if (!empty($insts)) : ?>
    <? if (!empty($courses)) : ?>
        <?= $this->render_partial('admin/courses/courses.php', compact('courses')) ?>
    <? else : ?>
        <? if ($count_courses) : ?>
            <?= MessageBox::info(sprintf(_('Es wurden %s Veranstaltungen gefunden. Grenzen Sie diese mit den Filtermöglichkeiten weiter ein.'), $count_courses)) ?>
        <? else : ?>
            <?= MessageBox::info(_('Ihre Suche ergab keine Treffer')) ?>
        <? endif ?>
    <? endif ?>
<? else : ?>
    <?= MessageBox::info(sprintf(_('Sie wurden noch keinen Einrichtungen zugeordnet. Bitte wenden Sie sich an einen der zuständigen %sAdministratoren%s.'), '<a href="' . URLHelper::getLink('dispatch.php/siteinfo/show') . '">', '</a>')) ?>
<? endif ?>
