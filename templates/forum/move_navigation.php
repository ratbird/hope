<? use Studip\Button, Studip\LinkButton; ?>
<tr>
    <td class="blank" colspan="2">

        <table class="default">
            <colgroup>
                <col width="20%">
                <col width="80%">
            </colgroup>
            <thead>
                <tr>
                    <th colspan="2" style="text-align: left;">
                        <?= Assets::img('icons/16/yellow/arr_2right', array('class' => 'text-top')) ?>
                        <?= sprintf(_('Als Thema verschieben (zusammen mit %s Antworten):'), $count) ?>
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr class="table_row_even">
                    <td align="right">
                        <label for="seminars">
                            <?= _('in das Forum einer Veranstaltung:') ?>
                        </label>
                    </td>
                    <td>
                        <form action="<?= URLHelper::getLink('') ?>" method="post">
                            <?= CSRFProtection::tokenTag() ?>
                            <input type="image" name="submit" value="Verschieben"
                                   src="<?= Assets::image_path('icons/16/yellow/arr_2right') ?>"
                                   <?= tooltip(_('dahin verschieben')) ?>
                                   style="margin: 0 1em; vertical-align: text-top;">
                            <select name="sem_id" id="seminars">
                            <? foreach ($seminars as $id => $name): ?>
                                <option value="<?= $id ?>"
                                        <? if ($id == $current_seminar) echo 'selected'; ?>>
                                    <?= htmlReady(substr($name, 0, 50)) ?>
                                </option>
                            <? endforeach; ?>
                            </select>
                            <input type="hidden" name="target" value="Seminar">
                            <input type="hidden" name="topic_id" value="<?= $topic_id ?>">
                        </form>
                    </td>
                </tr>
            <? if (count($institutes) > 0): ?>   
                <tr class="table_row_even">
                    <td align="right">
                        <label for="institutes">
                            <?= _('in das Forum einer Einrichtung:') ?>
                        </label>
                    </td>
                    <td>
                        <form action="<?= URLHelper::getLink('') ?>" method="post">
                            <?= CSRFProtection::tokenTag() ?>
                            <input type="image" name="submit" value="Verschieben"
                                   src="<?= Assets::image_path('icons/16/yellow/arr_2right') ?>"
                                   <?= tooltip(_('dahin verschieben')) ?>
                                   style="margin: 0 1em; vertical-align: text-top;">
                            <select name="inst_id" id="institutes">
                            <? foreach ($institutes as $id => $name): ?>
                                <option value="<?= $id ?>">
                                    <?= htmlReady(substr($name, 0, 50)) ?>
                                </option>
                            <? endforeach; ?>
                            </select>
                            <input type="hidden" name="target" value="Institut">
                            <input type="hidden" name="topic_id" value="<?= $topic_id ?>">
                        </form>
                    </td>
                </tr>
            <? endif; ?>
            </tbody>
            <tfoot>
                <tr class="table_footer" valign="middle">
                    <td>&nbsp;</td>
                    <td><?= LinkButton::createCancel() ?></td>
                </tr>
            </tfoot>
        </table>

    </td>
</tr>
