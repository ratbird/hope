<tbody class="collapsed">
    <tr class="header-row">
        <th colspan="3" class="toggle-indicator">
            <a class="toggler"><?= _('Teilnehmer hinzufügen') ?>
            </a>
        </th>
    </tr>
    <tr>
        <td colspan="3">
            <div>
                <label for="user_id_1"><h4><?= _('Teilnehmer') ?></h4></label>
                <ul class="clean" id="adressees">
                    <li id="template_adressee" style="display: none;" class="adressee">
                        <input type="hidden" name="attendees[]" value="">
                        <span class="visual"></span>
                        <a class="remove_adressee"><?= Assets::img("icons/16/blue/trash", array('class' => "text-bottom")) ?></a>
                    </li>
                    <? foreach ($attendees as $attendee) : ?>
                    <? $user = $attendee->user; ?>
                    <? if ($user) : ?>
                    <li style="padding: 0px;" class="adressee">
                        <input type="hidden" name="attendees[]" value="<?= htmlReady($user['user_id']) ?>">
                        <span class="visual">
                            <?= htmlReady($user->getFullname()) ?>
                        </span>
                        <a class="remove_adressee"><?= Assets::img("icons/16/blue/trash", array('class' => "text-bottom")) ?></a>
                    </li>
                    <? endif; ?>
                    <? endforeach ?>
                </ul>
                <?= $quick_search->render() ?>
                <?= $mps->render(); ?>
                <script>
                    STUDIP.MultiPersonSearch.init();
                </script>
            </div>
        </td>
    </tr>
</tbody>