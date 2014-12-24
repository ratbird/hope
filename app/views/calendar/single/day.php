<?
// add skip link
SkipLinks::addIndex(_("Tagesansicht"), 'main_content', 100);
?>
<table class="blank" border="0" cellpadding="10" cellspacing="0" width="100%" id="main_content">
    <tr>
        <td valign="top" class="blank" nowrap="nowrap" width="70%">
            <?= $this->render_partial('calendar/single/_day'); ?>
        </td>
        <td valign="top" align="left" class="blank" width="30%">
            <? $imt = Request::int('imt', mktime(12, 0, 0, date('n', $atime) - 1, date('j', $atime), date('Y', $atime))) ?>
            <?= $this->render_partial('calendar/single/_include_month', array('imt' => $imt, 'href' => '')) ?>
            <? $imt = mktime(12, 0, 0, date('n', $imt) + 1, date('j', $imt), date('Y', $imt)) ?>
            <?= $this->render_partial('calendar/single/_include_month', array('imt' => $imt, 'href' => '', 'mod' => 'NONAVARROWS')) ?>
            <? $imt = mktime(12, 0, 0, date('n', $imt) + 1, date('j', $imt), date('Y', $imt)) ?>
            <?= $this->render_partial('calendar/single/_include_month', array('imt' => $imt, 'href' => '', 'mod' => 'NONAVARROWS')) ?>
        </td>
    </tr>
</table>