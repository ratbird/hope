<? if (!empty($studygroups)) : ?>
    <table class="default" id="my_seminars">
        <caption>
            <?= sprintf(_('Studiengruppen im %s'), htmlReady($semester->name)) ?>
        </caption>
        <colgroup>
            <col width="10px">
            <col width="25px">
            <col>
            <col width="<?= $nav_elements * 27 ?>px">
            <col width="45px">
        </colgroup>
        <thead>
            <tr>
                <th colspan="2" nowrap align="center">
                    <a href="<?= URLHelper::getLink('dispatch.php/my_courses/groups') ?>">
                        <?= Assets::img('icons/20/blue/group.png', array('title' => _('Gruppe �ndern'), 'class' => 'middle')) ?>
                    </a>
                </th>
                <th><?= _('Name') ?></th>
                <th><?= _('Inhalt') ?></th>
                <th></th>
            </tr>
        </thead>
        <?= $this->render_partial('my_studygroups/_course', compact('courses')) ?>
    </table>
<? else : ?>
    <?= MessageBox::info(_('Sie haben bisher noch keine Studiengruppe gegr�ndet oder sich in eine eingetragen.')) ?>
<? endif ?>
