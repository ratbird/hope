<? if (!empty($studygroups)) : ?>
    <table class="default" id="my_seminars">
        <caption>
            <?= sprintf(_("Studiengruppen im %s"), $semester->name) ?>
        </caption>
        <colgroup>
            <col width="10px">
            <col width="25px">
            <col>
            <col width="35%">
            <col width="3%">
        </colgroup>
        <thead>
        <tr>
            <th colspan="2" nowrap="nowrap" align="center">
                <a href="<?= URLHelper::getLink('dispatch.php/my_courses/groups') ?>">
                    <?= Assets::img('icons/20/blue/group.png', array('title' => _("Gruppe ä ndern"), 'class' => 'middle')) ?>
                </a>
            </th>
            <th><?= _("Name") ?></th>
            <th><?= _("Inhalt") ?></th>
            <th></th>
        </tr>
        </thead>
        <?= $this->render_partial("my_studygroups/_course", compact('courses')) ?>
    </table>
<? else : ?>
    <?= MessageBox::info(_('Sie haben sich bisher noch in keine Studiengruppen eingetragen oder gegründet!')) ?>
<? endif ?>
