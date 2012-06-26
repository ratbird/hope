<?
use Studip\Button, Studip\LinkButton;

$css_switcher = new cssClassSwitcher();
$css_switcher->switchClass();
?>


<table width="100%" cellspacing="0" cellpadding="0" border="0" align="center">
    <tr>
        <td class="blank" width="100%" colspan="2" align="center"><br>
            <p class="info">
            <b><?= _("Hier k&ouml;nnen Sie die Ansicht Ihres pers&ouml;nlichen Terminkalenders anpassen."); ?></b>
            </p>
            <form method="post" action="<? echo URLHelper::getLink('?cmd_cal=chng_cal_settings')?>">
            <?= CSRFProtection::tokenTag() ?>
            <table width ="70%" align="center" cellspacing="0" cellpadding="8" border="0" id="main_content">
                <tr>
                    <th width="50%" align=center><?=_("Option")?></th>
                    <th align="center"><?=_("Auswahl")?></th>
                </tr>
                <tr <? $css_switcher->switchClass() ?>>
                    <td colspan="2" align="center" class="steelgraulight" style="border-bottom:1px dotted black;border-top:1px dotted black;">
                        <b><?=_("Allgemeine Optionen")?></b>
                    </td>
                </tr>
                <tr>
                    <td align="right" class="blank" style="border-bottom:1px dotted black;">
                        <label for="cal_view"><? echo _("Startansicht anpassen"); ?></label>
                    </td>
                    <td class="<? echo $css_switcher->getClass(); ?>">
                        <select name="cal_view" id="cal_view" size="1">
                            <option value="showday"<? if ($calendar_user_control_data['view'] == 'showday') : echo ' selected'; endif ?>><?= _("Tagesansicht") ?></option>
                            <option value="showweek"<? if ($calendar_user_control_data['view'] == 'showweek') : echo ' selected'; endif ?>><?= _("Wochenansicht") ?></option>
                            <option value="showmonth"<? if ($calendar_user_control_data['view'] == 'showmonth') : echo ' selected'; endif ?>><?= _("Monatsansicht") ?></option>
                            <option value="showyear"<? if ($calendar_user_control_data['view'] == 'showyear') : echo ' selected'; endif ?>><?= _("Jahresansicht") ?></option>
                        </select>
                    </td>
                </tr>
                <tr><? $css_switcher->switchClass(); ?>
                    <td align="right" class="blank" style="border-bottom:1px dotted black;">
                        <? echo _("Wochenansicht anpassen"); ?>
                    </td>
                    <td class="<? echo $css_switcher->getClass(); ?>">
                        <label><input type="radio" name="cal_type_week" value="LONG"<? if ($calendar_user_control_data['type_week'] == 'LONG') : echo ' checked'; endif ?>>&nbsp;<?= _("7 Tage-Woche") ?></label><br>
                        <label><input type="radio" name="cal_type_week" value="SHORT"<? if ($calendar_user_control_data['type_week'] == 'SHORT') : echo ' checked'; endif ?>>&nbsp;<?= _("5 Tage-Woche") ?></label>
                    </td>
                </tr>
                <tr><? $css_switcher->switchClass(); ?>
                    <td align="right" class="blank">
                        <label for="cal_delete"><?= _("L&ouml;schen von Terminen"); ?></label>
                    </td>
                    <td class="<? echo $css_switcher->getClass(); ?>">
                        <select name="cal_delete" id="cal_delete" size="1">
                            <option value="12"<? if ($calendar_user_control_data['delete'] == 12) : echo ' selected'; endif ?>><?= _("12 Monate nach Ablauf"); ?></option>
                            <option value="6"<? if ($calendar_user_control_data['delete'] == 6) : echo ' selected'; endif ?>><?= _("6 Monate nach Ablauf"); ?></option>
                            <option value="3"<? if ($calendar_user_control_data['delete'] == 3) : echo ' selected'; endif ?>><?= _("3 Monate nach Ablauf"); ?></option>
                            <option value="0"<? if ($calendar_user_control_data['delete'] == 0) : echo ' selected'; endif ?>><?= _("nie"); ?></option>
                        </select>
                    </td>
                </tr>
                <? if (get_config('CALENDAR_GROUP_ENABLE')) : ?>
                <tr <? $css_switcher->switchClass() ?>>
                    <td colspan="2" align="center" class="steelgraulight" style="border-bottom:1px dotted black;border-top:1px dotted black;">
                        <b><?=_("Einzelterminkalender")?></b>
                    </td>
                </tr>
                <? endif ?>
                <tr><? $css_switcher->switchClass(); ?>
                <td align="right" class="blank" style="border-bottom:1px dotted black;">
                    <? echo _("Zeitraum der Tages- und Wochenansicht"); ?>
                </td>
                <td class="<? echo $css_switcher->getClass(); ?>">
                    <select name="cal_start" aria-label="<?= _("Startzeit der Tages- und Wochenansicht") ?>">
                    <?
                    for ($i = 0; $i <= 23; $i++) :
                        if ($i == $calendar_user_control_data['start']) :
                            echo '<option selected value=' . $i . '>';
                            if ($i < 10) :
                                echo '0' . $i . ':00';
                            else :
                                echo $i . ":00";
                            endif;
                         else :
                            echo '<option value=' . $i . '>';
                            if ($i < 10) :
                                echo '0' . $i . ':00';
                            else :
                                echo $i . ':00';
                            endif;
                        endif;
                        echo '</option>';
                    endfor
                    ?>
                    </select>&nbsp;<?= _("Uhr bis"); ?>
                    <select name="cal_end" aria-label="<?= _("Endzeit der Tages- und Wochenansicht") ?>">
                    <?
                    for ($i = 0; $i <= 23; $i++) :
                        if ($i == $calendar_user_control_data['end']) :
                            echo '<option selected value=' . $i . '>';
                            if ($i < 10) :
                                echo '0' . $i . ':00';
                            else :
                                echo $i . ':00';
                            endif;
                        else :
                            echo '<option value=' . $i . '>';
                            if ($i < 10) :
                                echo '0' . $i . ':00';
                            else :
                                echo $i . ':00';
                            endif;
                        endif;
                        echo "</option>";
                    endfor;
                    ?>
                    </select>&nbsp;<?= _("Uhr."); ?>
                    </td>
                </tr>
                <tr><? $css_switcher->switchClass(); ?>
                    <td align="right" class="blank" style="border-bottom:1px dotted black;">
                        <label for="cal_step_day"><?= _("Zeitintervall der Tagesansicht"); ?></label>
                    </td>
                    <td class="<?= $css_switcher->getClass(); ?>">
                        <select name="cal_step_day" for="cal_step_day" size="1">
                            <option value="600"<? if ($calendar_user_control_data['step_day'] == 600) : echo ' selected'; endif ?>><?= _("10 Minuten") ?></option>
                            <option value="900"<? if ($calendar_user_control_data['step_day'] == 900) : echo ' selected'; endif ?>><?= _("15 Minuten") ?></option>
                            <option value="1800"<? if ($calendar_user_control_data['step_day'] == 1800) : echo ' selected'; endif ?>><?= _("30 Minuten"); ?></option>
                            <option value="3600"<? if ($calendar_user_control_data['step_day'] == 3600) : echo ' selected'; endif ?>><?= _("1 Stunde"); ?></option>
                            <option value="7200"<? if ($calendar_user_control_data['step_day'] == 7200) : echo ' selected'; endif ?>><?= _("2 Stunden"); ?></option>
                        </select>
                    </td>
                </tr>
                <tr><? $css_switcher->switchClass(); ?>
                    <td align="right" class="blank">
                        <label for="cal_step_week"><?= _("Zeitintervall der Wochenansicht"); ?></label>
                    </td>
                    <td class="<?= $css_switcher->getClass(); ?>">
                        <select name="cal_step_week" id="cal_step_week" size="1">
                            <option value="1800"<? if ($calendar_user_control_data["step_week"] == 1800) : echo ' selected'; endif ?>><?= _("30 Minuten"); ?></option>
                            <option value="3600"<? if ($calendar_user_control_data["step_week"] == 3600) : echo ' selected'; endif ?>><?= _("1 Stunde"); ?></option>
                            <option value="7200"<? if ($calendar_user_control_data["step_week"] == 7200) : echo ' selected'; endif ?>><?= _("2 Stunden"); ?></option>
                        </select>
                    </td>
                </tr>
        <?/*
                <tr><? $css_switcher->switchClass(); ?>
                    <td class="<? echo $css_switcher->getClass(); ?>">
                        <div class="indent">
                       <br><b><? echo _("Feiertage/Semesterdaten:"); ?></b>
                        </div>
                    </td>
                    <td class="<? echo $css_switcher->getClass(); ?>"><br>
                        <label><input type="checkbox" name="cal_holidays" value="TRUE"<?
                            if($calendar_user_control_data["holidays"])
                                echo " checked";
                            echo '>&nbsp;' . _("Feiertage anzeigen") . "</label><br>"; ?>
                        <label><input type="checkbox" name="cal_sem_data" value="5"<?
                            if($calendar_user_control_data["sem_data"])
                                echo " checked";
                            echo ">&nbsp;" . _("Semesterdaten anzeigen"); ?>
                    </td>
                </tr>
        */

            if (get_config('CALENDAR_GROUP_ENABLE')) :
            ?>
                <tr <? $css_switcher->switchClass() ?>>
                    <td colspan="2" align="center" class="steelgraulight" style="border-bottom:1px dotted black;border-top:1px dotted black;">
                        <b><?= _("Gruppenterminkalender") ?></b>
                    </td>
                </tr>
                <tr><? $css_switcher->switchClass(); ?>
                    <td align="right" class="blank" style="border-bottom:1px dotted black;">
                        <label for="cal_step_day_group"><?= _("Zeitintervall der Tagesansicht"); ?></label>
                    </td>
                    <td class="<?= $css_switcher->getClass(); ?>">
                        <select name="cal_step_day_group" id="cal_step_day_group" size="1">
                            <option value="900"<? if ($calendar_user_control_data["step_day_group"] == 900) : echo ' selected'; endif ?>><?= _("15 Minuten"); ?></option>
                            <option value="1800"<? if ($calendar_user_control_data["step_day_group"] == 1800) : echo ' selected'; endif ?>><?= _("30 Minuten"); ?></option>
                            <option value="3600"<? if ($calendar_user_control_data["step_day_group"] == 3600) : echo ' selected'; endif ?>><?= _("1 Stunde"); ?></option>
                            <option value="7200"<? if ($calendar_user_control_data["step_day_group"] == 7200) : echo ' selected'; endif ?>><?= _("2 Stunden"); ?></option>
                        </select>
                    </td>
                </tr>
                <tr><? $css_switcher->switchClass(); ?>
                    <td align="right" class="blank">
                        <label for="cal_step_week_group"><?= _("Zeitintervall der Wochenansicht"); ?></label>
                    </td>
                    <td class="<?= $css_switcher->getClass(); ?>">
                        <select name="cal_step_week_group" id="cal_step_week_group" size="1">
                            <option value="1800"<? if($calendar_user_control_data["step_week_group"] == 1800) : echo ' selected'; endif ?>><?= _("30 Minuten"); ?></option>
                            <option value="3600"<? if($calendar_user_control_data["step_week_group"] == 3600) : echo ' selected'; endif ?>><?= _("1 Stunde"); ?></option>
                            <option value="7200"<? if($calendar_user_control_data["step_week_group"] == 7200) : echo ' selected'; endif ?>><?= _("2 Stunden"); ?></option>
                        </select>
                    </td>
                </tr>
            <?
            endif
            ?>
                <tr><? $css_switcher->switchClass(); ?>
                    <td class="<?= $css_switcher->getClass(); ?>" colspan="2" align="center">
                    <? if (Request::option('atime')) : ?>
                        <input type="hidden" name="atime" value="<?= Request::option('atime') ?>">
                    <? endif ?>
                    <input type="hidden" name="view" value="calendar">
                    <?= Button::create(_('Übernehmen'), array('title' => _("Änderungen übernehmen")))?>
                    </td>
                </tr>
            </table>
            </form>
            <br><br>
        </td>
    </tr>
</table>
