<table class="default">
    <caption>
        <span class="actions" style="font-size: 0.9em;">
            <?= _('Ihre Punkte:') ?>
            <strong><?= number_format($this->current_user_score, 0, ',', '.') ?></strong>
            (<?= Score::getTitel($this->current_user_score, $this->current_user->geschlecht) ?>)
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
    <? foreach ($persons as $index => $person): ?>
        <tr>
            <td style="text-align: right;">
                <?= $offset + $index + 1 ?>.
            </td>
            <td>
                <?= Avatar::getAvatar($person['user_id'], $person['username'])->getImageTag(Avatar::SMALL, array('title' => $person['fullname'])) ?>
            </td>
            <td>
                <a href="<?= URLHelper::getLink('dispatch.php/profile?username='. $person['username']) ?>">
                    <?= htmlReady($person['fullname']) ?>
                </a>
            <? foreach ($person['is_king'] as $type => $text): ?>
                <?= Icon::create('crown', 'sort', ['title' => $text])->asImg(16, ["alt" => $text, "class" => 'text-top']) ?>
            <? endforeach ?>
            </td>
            <td>
            <?
            $content = Assets::img('blank.gif', array('width' => 16)) . ' ';

            // News
            if ($news = $person['newscount']) {
                $tmp = sprintf(ngettext('Eine pers�nliche Ank�ndigung', '%s pers�nliche Ank�ndigungen', $news), $news);
                $content .= sprintf('<a href="%s">%s</a> ',
                                    URLHelper::getLink('dispatch.php/profile', compact('username')),
                                    Icon::create('news', 'clickable', ['title' => $tmp])->asImg());
            } else {
                $content .= Assets::img('blank.gif', array('width' => 16)) . ' ';
            }

            // Votes
            if ($vote = $person['votecount']) {
                $tmp = sprintf(ngettext('Eine Umfrage', '%s Umfragen', $vote), $vote);
                $content .= sprintf('<a href="%s">%s</a> ',
                                    URLHelper::getLink('dispatch.php/profile', compact('username')),
                                    Icon::create('vote', 'clickable', ['title' => $tmp])->asImg());
            } else {
                $content .= Assets::img('blank.gif', array('width' => 16)) . ' ';
            }

            // Termine
            if ($termin = $person['eventcount']) {
                $tmp = sprintf(ngettext('Ein Termin', '%s Termine', $termin), $termin);
                $content .= sprintf('<a href="%s">%s</a> ',
                                    URLHelper::getLink('dispatch.php/profile#a', compact('username')),
                                    Icon::create('schedule', 'clickable', ['title' => $tmp])->asImg());
            } else {
                $content .= Assets::img('blank.gif', array('width' => 16)) . ' ';
            }

            // Literaturangaben
            if ($lit = $person['litcount']) {
                $tmp = sprintf(ngettext('Eine Literaturangabe', '%s Literaturangaben', $lit), $lit);
                $content .= sprintf('<a href="%s">%s</a> ',
                                    URLHelper::getLink('dispatch.php/profile', compact('username')),
                                    Icon::create('literature', 'clickable', ['title' => $tmp])->asImg());
            } else {
                $content .= Assets::img('blank.gif', array('width' => 16)) . ' ';
            }

            echo $content;
            ?>
            </td>
            <td><?= number_format($person['score'], 0, ',', '.') ?></td>
            <td><?= Score::getTitel($person['score'], $person['geschlecht']) ?></td>
            <td style="text-align: right">
            <? if($person['user_id'] == $GLOBALS['user']->id): ?>
                <a href="<?= $controller->url_for('score/unpublish') ?>">
                    <?= Icon::create('trash', 'clickable', ['title' => _('Ihren Wert von der Liste l�schen')])->asImg(16, ["class" => 'text-top']) ?>
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
