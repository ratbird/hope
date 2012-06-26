<?
// add skip link
SkipLinks::addIndex(_("Tagesansicht"), 'main_content', 100);
?>
<table id="main_content" width="100%" border="0" cellpadding="5" cellspacing="0">
<? if (get_config('CALENDAR_GROUP_ENABLE')) : ?>
    <tr>
        <td class="blank" width="25%" nowrap="nowrap">
            <?= $this->render_partial('calendar/_jump_to'); ?>
        </td>
        <td class="blank" width="25%" nowrap="nowrap">
            <?= $this->render_partial('calendar/_select_category'); ?>
        </td>
        <td class="blank" width="50%">
            <?= $this->render_partial('calendar/_select_calendar'); ?>
        </td>
    </tr>
<?  else : ?>
    <tr>
        <td class="blank" nowrap="nowrap">
            <?= $this->render_partial('calendar/_jump_to'); ?>
        </td>
        <td class="blank">
            <?= $this->render_partial('calendar/_select_category'); ?>
        </td>
    </tr>
<? endif ?>
</table>
<table class="blank" border="0" cellpadding="10" cellspacing="0" width="100%">
    <tr>
        <td valign="top" class="blank" nowrap="nowrap" width="70%">
            <?= $_calendar->toStringDay($atime, $st, $et, Request::get('cal_restrict'), Calendar::getBindSeminare($_calendar->getUserId())) ?>
        </td>
        <td valign="top" align="left" class="blank" width="30%">
            <? $imt = Request::int('imt', mktime(12, 0, 0, date('n', $atime) - 1, date('j', $atime), date('Y', $atime))) ?>
            <?= includeMonth($imt, "?cmd=showday&atime=", '', '', $atime) ?>
            <? $imt = mktime(12, 0, 0, date('n', $imt) + 1, date('j', $imt), date('Y', $imt)) ?>
            <?= includeMonth($imt, "?cmd=showday&atime=", 'NONAVARROWS', '', $atime) ?>
            <? $imt = mktime(12, 0, 0, date('n', $imt) + 1, date('j', $imt), date('Y', $imt)) ?>
            <?= includeMonth($imt, "?cmd=showday&atime=", 'NONAVARROWS', '', $atime) ?>
        </td>
    </tr>
</table>