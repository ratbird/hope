<?
# Lifter010: TODO
?>
<table class="infobox" width="260" border="0" cellpadding="0" cellspacing="0">
    <!-- Bild -->
    <tr>
        <td class="infobox-img">
            <?= Assets::img($picture, array('size' => '260@96')) ?>
        </td>
    </tr>

    <tr>
        <td class="infoboxrahmen" width="100%">
            <table align="center" width="99%" border="0" cellpadding="4" cellspacing="0">
                <colgroup>
                    <col width="1%">
                    <col width="99%">
                </colgroup>

                <!-- Informationen -->
                <tr>
                    <td colspan="2">
                        <b><?= _('Informationen') ?>:</b>
                        <br>
                    </td>
                </tr>

                <tr>
                    <td align="center" valign="top">
                        <?= Assets::img('icons/16/black/info.png') ?>
                    </td>
                    <td align="left">
                        <?= _('Hier können Sie für die einzelnen Termine Beschreibungen eingeben, Themen im Forum und Dateiordner anlegen.')?>
                        <br>
                    </td>
                </tr>

                <tr>
                    <td align="center" valign="top">
                        <?= Assets::img('icons/16/black/info.png') ?>
                    </td>
                    <td align="left">
                        <?= sprintf(_('Zeitänderungen, Raumbuchungen und Termine anlegen können Sie unter %sZeiten%s.'),
                                    '<a href="'. URLHelper::getLink('raumzeit.php') . '">', '</a>') ?>
                        <br>
                        <br>
                        <?= $times_info ?>
                    </td>
                </tr>

                <!-- Ansicht -->
            <? if ($GLOBALS['RESOURCES_ENABLE_EXPERT_SCHEDULE_VIEW']): ?>
                <tr>
                    <td colspan="2">
                        <b><?= _('Ansicht') ?>:</b>
                        <br>
                    </td>
                </tr>

                <tr>
                    <td align="center" valign="top">
                        <?= Assets::img('icons/16/red/arr_1right.png') ?>
                    </td>
                    <td align="left">
                        <a href="<?= URLHelper::getLink('themen.php?cmd=changeViewMode&newFilter=simple') ?>">
                            <?= _('Standardansicht') ?>
                        </a>
                        <br>
                    </td>
                </tr>

                <tr>
                    <td align="center" valign="top">
                        <?= Assets::img('icons/16/black/arr_1right.png') ?>
                    </td>
                    <td align="left">
                        <a href="<?= URLHelper::getLink('themen.php?cmd=changeViewMode&newFilter=expert') ?>">
                            <?= _('Erweiterte Ansicht') ?>
                        </a>
                        <br>
                    </td>
                </tr>
            <? endif ?>

                <!-- Semesterauswahl -->
                <?= $this->render_partial("infobox/infobox_dropdownlist_partial.php") ?>

                <!-- Aktionen -->
                <tr>
                    <td colspan="2">
                        <b><?= _('Aktionen') ?>:</b>
                        <br>
                    </td>
                </tr>

                <tr>
                    <td align="center" valign="top">
                        <?= Assets::img('icons/16/black/schedule.png') ?>
                    </td>
                    <td align="left">
                        <a href="<?= URLHelper::getLink('raumzeit.php?cmd=createNewSingleDate#newSingleDate') ?>">
                            <?= _('Einen neuen Termin anlegen') ?>
                        </a>
                        <br>
                    </td>
                </tr>
            </table>

        </td>
    </tr>
</table>

