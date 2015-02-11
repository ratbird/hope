<?php
$formatnumber = function ($number, $digits = 0) {
    $locale = localeconv();
    return number_format($number, $digits, $locale['decimal_point'], $locale['thousands_sep']);
};
?>
<section class="document-dialog">
    <aside>
        <div class="document-dialog-icon">
        <? if ($icon): ?>
            <?= Assets::img($icon) ?>
        <? else: ?>
            <?= Assets::img('icons/100/lightblue/file-generic.png') ?>
        <? endif; ?>
        </div>
    <? if ($entry): ?>
        <h3><?= htmlReady($entry->name) ?></h3>
        <dl>
            <dt><?= _('Größe') ?></dt>
            <dd><?= relsize($entry->getSize(), false) ?></dd>
            <dt><?= _('Downloads') ?></dt>
            <dd><?= $formatnumber($entry->downloads) ?>
            <dt><?= _('Erstellt') ?></dt>
            <dd>
                <?= strftime('%x %X', $entry->file->mkdate) ?>
            </dd>
        <? if ($entry->file->mkdate !== $entry->file->chdate): ?>
            <dt><?= _('Geändert') ?></dt>
            <dd>
                <?= strftime('%x %X', $entry->file->chdate) ?>
            </dd>
        <? endif; ?>
            <dt><?= _('Autor') ?></dt>
            <dd>
            <? if ($entry->file->owner->id !== $GLOBALS['user']->id): ?>
                <a href="<?= URLHelper::getLink('dispatch.php/profile?username=' . $entry->file->owner->username) ?>">
                    <?= htmlReady($entry->file->owner->getFullName()) ?>
                </a>
            <? else: ?>
                <?= htmlReady($entry->file->owner->getFullName()) ?>
            <? endif; ?>
            </dd>
        </dl>
    <? elseif ($folder): ?>
        <h3><?= htmlReady($folder->name) ?></h3>
        <dl>
            <dt><?= _('Inhalt') ?></dt>
            <dd><?= sprintf(ngettext('%u Eintrag', '%u Einträge', $count = $folder->file->countFiles()), $count) ?></dd>
            <dt><?= _('Größe') ?></dt>
            <dd><?= relsize($folder->getSize(), false) ?></dd>
            <dt><?= _('Downloads') ?></dt>
            <dd><?= $formatnumber($folder->downloads) ?>
            <dt><?= _('Erstellt') ?></dt>
            <dd>
                <?= strftime('%x %X', $folder->file->mkdate) ?>
            </dd>
        <? if ($folder->file->mkdate !== $folder->file->chdate): ?>
            <dt><?= _('Geändert') ?></dt>
            <dd>
                <?= strftime('%x %X', $folder->file->chdate) ?>
            </dd>
        <? endif; ?>
            <dt><?= _('Autor') ?></dt>
            <dd>
            <? if ($folder->file->owner->id !== $GLOBALS['user']->id): ?>
                <a href="<?= URLHelper::getLink('dispatch.php/profile?username=' . $folder->file->owner->username) ?>">
                    <?= htmlReady($folder->file->owner->getFullName()) ?>
                </a>
            <? else: ?>
                <?= htmlReady($folder->file->owner->getFullName()) ?>
            <? endif; ?>
            </dd>
        </dl>
    <? endif; ?>
    </aside>
    <div>
        <?= $content_for_layout ?>
    </div>
</section>
