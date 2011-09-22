<form action="<?= URLHelper::getLink('', array('cmd' => $cmd, 'atime' => $atime)) ?>" method="post" name="jump_to">
    <span style="font-size: small; color: #555555;"><?= _("Gehe zu:") ?> </span>
    <input type="text" name="jmp_day" size="2" maxlength="2" value="<?= date('d', $atime) ?>">
    . <input type="text" name="jmp_month" size="2" maxlength="2" value="<?= date('m', $atime) ?>">
    . <input type="text" name="jmp_year" size="4" maxlength="4" value="<?= date('Y', $atime) ?>">
    <img src="<?= Assets::image_path('popupcalendar.png') ?>" onClick="window.open('<?= UrlHelper::getLink("termin_eingabe_dispatch.php?element_switch=jmp&submit=1&form_name=jump_to&mcount=6&imt=$atime&atime=$atime"); ?>', 'InsertDate', 'dependent=yes, width=700, height=450, left=250, top=150')" class="text-top">
    <input type="image" src="<?= Assets::image_path('icons/16/blue/accept.png') ?>" border="0" class="text-top">
</form>