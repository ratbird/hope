<?
// add skip link
SkipLinks::addIndex(_("Wochenansicht"), 'main_content', 100);
?>
<table id="main_content" width="100%" border="0" cellpadding="5" cellspacing="0" align="center">
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
<? else : ?>
    <tr>
        <td class="blank" nowrap="nowrap" colspan="2">
            <?= $this->render_partial('calendar/_jump_to'); ?>
        </td>
        <td class="blank">
            <?= $this->render_partial('calendar/_select_category'); ?>
        </td>
    </tr>
<? endif ?>
</table>
<table class="blank" border="0" cellpadding="0" cellspacing="0" style="width:100%; table-layout: fixed;">
    <tr>
        <td style="width:100%; overflow:hidden; padding:0 1%;">
            <div style="overflow:auto; width:100%;">
                <? //$_calendar->toStringWeek($week_time, $start_time, $end_time, $restrictions = NULL, $sem_ids = NULL) ?>
                <?= $_calendar->toStringWeek($atime, $st, $et, $_REQUEST['cal_restrict'], Calendar::getBindSeminare($_calendar->getUserId())); ?>
            </div>
        </td>
    </tr>
</table>
