<?
    $cssSw = new CSSClassSwitcher();
    $pos = 0;
    $style = "style=\"background-image: url('". Assets::image_path('forumstrich') ."');"
        ." background-position: right;"
        ." background-repeat: repeat-y;"
        ."\" ";

    if ($seminar_persons) :
        $width = '33%';
        $indirect = true;
    else :
        $width = '50%';
        $indirect = false;
    endif;
?>
<tr>
    <? for($i = 0; $i < $indent; $i++) : ?>
    <td class="blank" width="10" align="right" <?= ($followers[$i+1]) ? $style : '' ?>></td>
    <? endfor; ?>

    <? if ($has_child) : ?>
    <td class="blank" width="10" align="right" <?= $style ?> nowrap></td>
    <? else :
        $indent--;
    endif; ?>

    <td class="printcontent" colspan="<?= 19 - $indent ?>" width="100%">
    <table cellspacing="0" cellpadding="0" border="0" width="95%" style="margin: auto;padding-top:10px;padding-bottom:10px;">
        <!-- Person assigned to this role - Heading -->
        <tr>
            <td colspan="2" class="content_seperator">&nbsp;<b><?= $range_type == 'sem' ? _("Personen in dieser Gruppe") : _("Personen in dieser Rolle") ?></b>
            </td>
            <td class="content_seperator" width="5%" nowrap><?= ($role->getSize()) ? sizeof($persons) .' '._("von").' '. $role->getSize() : '' ?>
            &nbsp;</td>
            <td class="content_seperator" width="1%" nowrap><?= ($role->getSelfassign()) ? Assets::img('icons/16/grey/info-circle.png', array('title' => _("Personen können sich dieser Gruppe selbst zuordnen"))) : '' ?>
            </td>
        </tr>
        <!-- Persons assigned to this role -->
        <? if (is_array($persons)) foreach ($persons as $person) :
        $cssSw->switchClass();
        $pos ++;
        ?>
        <tr>
            <td class="<?= $cssSw->getClass() ?>" width="1%" nowrap>&nbsp;&nbsp;<?= $pos ?>&nbsp;
            </td>
            <td colspan="3" class="<?= $cssSw->getClass() ?>">
            <? if ($range_type == 'sem') : ?>
                 <a href="about.php?username=<?= $person['username'] ?>">
            <? else: ?>
                 <a href="dispatch.php/settings/statusgruppen/switch/<?= $role_id ?>/1?username=<?= $person['username'] ?>#<?= $role_id ?>">
            <? endif; ?>
                 <?= htmlReady($person['fullname']) ?> </a>
            </td>
        </tr>
        <? endforeach; ?>
    </table>
    </td>
</tr>