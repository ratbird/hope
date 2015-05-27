<h1><?= _('Meine Veranstaltungen an meinen Einrichtungen') ?></h1>
<? if (!empty($insts)) : ?>
    <? if (!empty($courses)) : ?>
        <?= $this->render_partial('admin/courses/courses.php', compact('courses')) ?>
    <? elseif (empty($courses) && Request::get('search')) : ?>
        <?= MessageBox::info(_('Ihre Suche ergab kein Treffer')) ?>
        <? elseif($selected_inst_id == 'all' &&  !Request::get('search')) : ?>
        <?= MessageBox::info(_('Bitte geben Sie zunächst einen Suchbegriff ein'))?>
    <? else : ?>
        <?= MessageBox::info(sprintf(_('Im %s sind bisher keine Veranstaltungen vorhanden.'), $semester->name)); ?>
    <? endif ?>
<? else : ?>
    <?= MessageBox::info(sprintf(_('Sie wurden noch keinen Einrichtungen zugeordnet. Bitte wenden Sie sich an einen der zuständigen %sAdministratoren%s.'), '<a href="' . URLHelper::getLink('dispatch.php/siteinfo/show') . '">', '</a>')) ?>
<? endif ?>
