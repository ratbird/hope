<?php
if ($_SESSION['seminar_change_view']['cid'] && $_SESSION['seminar_change_view']['cid'] == $GLOBALS['SessSemName'][1]) {
?>
<div class="messagebox messagebox_warning">
    <?= sprintf(_('Sie sehen die Veranstaltung so wie Teilnehmer mit der '.
        'Berechtigung "%s". Sie können die Ansicht %shier zurücksetzen%s.'), 
        $_SESSION['seminar_change_view']['perm'], 
        '<a href="'.URLHelper::getLink('dispatch.php/course/change_view', 
        array('cid' => Request::option('cid'))).'">', '</a>'); ?>
</div>
<?php
}
?>
<!--<div class="clear"></div>-->