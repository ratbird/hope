<? if (!count($my_stm)): ?>
    <?= Messagebox::info(_('Es sind zur Zeit keine Ihrer Veranstaltungen zu Modulen zugeordnet.')) ?>
<? else: ?>
<table class="default zebra-big-hover">
    <colgroup>
        <col width="70%">
        <col width="20%">
        <col width="10%">
    </colgroup>
    <thead>
        <tr>
            <th align="left"><?= _('Name') ?></th>
            <th align="left"><?= _('Verantwortlich') ?></th>
            <th align="left"><?= _('Status') ?></th>
        </tr>
    </thead>
<? foreach($my_stm as $stm_id => $sems): ?>
    <tbody>
        <tr class="steelgraudunkel" style="font-weight: bold;">
            <td>
                <a href="<?= URLHelper::getLink('stm_details.php?stm_instance_id=' . $stms[$stm_id]['id']) ?>" class="tree">
                    <?= htmlReady($stms[$stm_id]['displayname']) ?>
                </a>
            </td>
            <td>
                <a href="<?= URLHelper::getLink('about.php?username=' . get_username($stms[$stm_id]['responsible'])) ?>" class="tree">
                    <?= htmlReady(get_fullname($stms[$stm_id]['responsible'], 'no_title_short')) ?>
                </a>
            </td>
            <td><?= $stms[$stm_id]['complete'] ? _('Vollständig') : _('Unvollständig') ?></td>
        </tr>
    <? if (empty($sems)): ?>
        <tr>
            <td colspan="3">&nbsp;</td>
        </tr>
    <? endif; ?>
    <? foreach ($sems as $one_sem): ?>
        <tr>
            <td colspan="3">
                <a href="<?= URLHelper::getLink('details.php?sem_id=' . $one_sem['seminar_id']) ?>">
                    <?= htmlReady($one_sem['Name']) ?>
                    (
                        <?= htmlReady($one_sem['startsem']) ?>
                    <? if ($one_sem['startsem'] != $one_sem['endsem']): ?>
                        - <?= htmlReady($one_sem['endsem']) ?>
                    <? endif; ?>
                    )
                </a>
            </td>
        </tr>
    <? endforeach; ?>
    </tbody>
<? endforeach; ?>
</table>
<? endif; ?>
<? 
$infobox = array(
    'picture' => 'infobox/lectures.jpg',
    'content' => array(
        array(
            'kategorie'  => _('Information:'),
            'eintrag'    => array(
                array('icon' => 'icons/16/black/info.png',
                      'text'  => sprintf(_('Es sind zur Zeit %s Veranstaltungen zu Studienmodulen zugewiesen.'), $all_sems)
                ),
                array('icon' => 'blank.gif',
                      'text'  => sprintf(_('Sie sind in %s Modulen als Verantwortlicher eingetragen.'), $num_my_mod)
                ),
                array('icon' => 'icons/16/black/search.png',
                      'text'  => _('Um mehr Informationen über ein Studienmodul anzuzeigen, klicken Sie bitte aus den Namen des Moduls.')
                )
            )
        ),
        array(
            'kategorie' => _('Aktionen:'),
            'eintrag' => array(
                array('icon' => 'icons/16/black/search.png',
                      'text'  => sprintf(_('Um Informationen über alle Studienmodule anzuzeigen nutzen Sie die<br>'
                                          .'%sSuche nach Studienmodulen%s'),
                                         '<a href="' . URLHelper::getURL('sem_portal.php?view=mod&reset_all=TRUE') . '">',
                                         '</a>')
                )
            )
        )
    )
);
