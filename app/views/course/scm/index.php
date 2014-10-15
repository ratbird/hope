<?= $verification ?>

<section class="contentbox">
    <header>
        <h1>
            <?= Assets::img('icons/16/grey/infopage.png', array('class' => 'text-top')) ?>
            <?= htmlReady($scm->tab_name) ?>
        </h1>
        <nav>
            <span>
                <? if ($scm->user): ?>
                    <?= sprintf(_('Zuletzt geändert von %s am %s'), ObjectdisplayHelper::link($scm->user), strftime('%x, %X', $scm->chdate)) ?>
                <? else: ?>
                    <?= sprintf(_('Zuletzt geändert am %s'), strftime('%x, %X', $scm->chdate)) ?>
                <? endif; ?>
            </span>
            <? if ($priviledged): ?>
                <a href="<?= $controller->url_for('course/scm/edit/' . $scm->id) ?>" title="<?= _('Bearbeiten') ?>" data-dialog>
                    <?= Assets::img('icons/16/blue/admin.png') ?>
                </a>
                <? if (count($scms) > 1): ?>
                    <? if ($scm->position != 0): ?>
                        <a href="<?= $controller->url_for('course/scm/move/' . $scm->id) ?>" title="<?= _('Diese Seite an die erste Position setzen') ?>">
                            <?= Assets::img('icons/16/blue/arr_2up.png') ?>
                        </a>
                    <? endif; ?>
                    <a href="<?= $controller->url_for('course/scm/' . $scm->id . '?verify=delete') ?>" title="<?= _('Diese Seite löschen') ?>">
                        <?= Assets::img('icons/16/blue/trash.png') ?>
                    </a>
                <? endif; ?>
            <? endif; ?>
        </nav>
    </header>
    <section>
        <?= $scm->content ? formatReady($scm->content) : MessageBox::info(_('In diesem Bereich wurden noch keine Inhalte erstellt.')) ?>
    </section>
</section>
