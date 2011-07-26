<table width="100%" class="blank" border="0" cellpadding="0" cellspacing="0">
    <tr>
        <td class="blank" width="100%">
            <table class="steelgroup0" width="100%" border="0" cellpadding="0" cellspacing="0">
                <tr>
                    <td align="center" width="10%" height="40">
                        <a href="<?= URLHelper::getLink('', array('cmd' => 'showday', 'atime' => $atime - 86400)) ?>">
                            <img border="0" src="<?= Assets::image_path('icons/16/blue/arr_2left.png') ?>"<?= tooltip(_("zurück")) ?>>
                        </a>
                    </td>
                    <td class="calhead" width="80%">
                        <?= $calendar->view->toString('LONG') . ', ' . $calendar->view->getDate() ?>
                        <div style="text-align: center; font-size: 12pt; color: #bbb; height: auto; overflow: visible; font-weight: bold;"><? $hd = holiday($calendar->view->getTs()); echo $holiday['name']; ?></div>
                    </td>
                    <td align="center" width="10%">
                        <a href="<?= URLHelper::getLink('', array('cmd' => 'showday', 'atime' => $atime + 86400)) ?>">
                            <img border="0" src="<?= Assets::image_path('icons/16/blue/arr_2right.png') ?>"<?= tooltip(_("vor")) ?>>
                        </a>
                    </td>
                </tr>
            <? if ($start > 0) : ?>
                <tr><td align="center" colspan="3">
                        <a href="<?= URLHelper::getLink('', array('cmd' => 'showday', 'atime' => ($atime - (date('G', $atime) - $start + 1) * 3600))) ?>">
                            <img border="0" src="<?= Assets::image_path('icons/16/blue/arr_1up.png') ?>"<?= tooltip(_("zeig davor")) ?>>
                        </a>
                    </td>
                </tr>
            <? endif ?>
            </table>
        </td>
    </tr>
    <tr>
        <td class="blank">
            <table class="steelgroup0" width="100%" border="0" cellpadding="0" cellspacing="1">
                <?= $this->render_partial('calendar/day_table') ?>
            </table>
        </td>
    </tr>
<? if ($end < 23) : ?>
    <tr>
        <td align="center">
            <a href="<?= URLHelper::getLink('', array('cmd' => 'showday', 'atime' => ($atime + ($end - date('G', $atime) + 1) * 3600))) ?>">
                <img border="0" src="<?= Assets::image_path('icons/16/blue/arr_1down.png') ?>"<?= tooltip(_("zeig danach")) ?>>
            </a>
        </td>
    </tr>
<? else : ?>
    <tr>
        <td>&nbsp;</td>
    </tr>
<? endif ?>
</table>