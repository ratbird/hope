<table class="default">
    <caption>
        <span class="actions" style="font-size: 0.9em;">
            <?= _('Ihre Punkte:') ?>
            <strong><?= number_format($score->score, 0, ',', '.') ?></strong>
            (<?= $score->title ?>)
        </span>
        <?= _('Stud.IP-Rangliste')?>
    </caption>
    <colgroup>
        <col width="3%">
        <col width="1%">
        <col width="50%">
        <col width="15%">
        <col width="15%">
        <col width="15%">
        <col width="1%">
    </colgroup>
    <thead>
        <tr>
            <th><?= _('Platz') ?></th>
            <th>&nbsp;</th>
            <th><?= _('Name') ?></th>
            <th>&nbsp;</th>
            <th><?= _('Punkte') ?></th>
            <th><?= _('Titel') ?></th>
            <th>&nbsp;</th>
        </tr>
    </thead>
    <tbody>
    <? foreach ($persons as $person): ?>
        <tr>
            <td style="text-align: right;">
                <?= ++$offset ?>.
            </td>
            <td>
                <?= Avatar::getAvatar($person['user_id'])->getImageTag(Avatar::SMALL) ?>
            </td>
            <td>
                <?= ObjectdisplayHelper::link($person->user) ?>
            <? foreach ($person->king as $type => $text): ?>
                <?= Assets::img('icons/16/yellow/crown.png', array('alt' => $text, 'title' => $text, 'class' => 'text-top')) ?>
            <? endforeach ?>
            </td>
            <td><?= $person->GetScoreContent() ?></td>
            <td><?= number_format($person['score'], 0, ',', '.') ?></td>
            <td><?= $person->title ?></td>
            <td style="text-align: right">
            <? if($person['user_id'] == $GLOBALS['user']->id): ?>
                <a href="<?= $controller->url_for('score/unpublish') ?>">
                    <?= Assets::img('icons/16/blue/trash.png',
                                    array('title' => _('Ihren Wert von der Liste löschen'),
                                          'class' => 'text-top')) ?>
                </a>
            <? endif; ?>
            </td>
        </tr>
    <? endforeach ?>
    </tbody>
<? if (ceil($numberOfPersons / $max_per_page) > 1): ?>
    <tfoot>
        <tr>
            <td colspan="7" style="text-align: right">
                <?= $GLOBALS['template_factory']->render('shared/pagechooser', array(
                        'perPage'      => $max_per_page,
                        'num_postings' => $numberOfPersons,
                        'page'         => $page,
                        'pagelink'     => 'dispatch.php/score/%u')) ?>
            </td>
        </tr>
    </tfoot>
<? endif ?>
</table>
