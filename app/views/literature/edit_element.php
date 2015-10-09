<section id="lit_edit_element">
    <? if ($msg) : ?>
        <table width="99%" border="0" cellpadding="2" cellspacing="0">
            <?= parse_msg($msg, '§', 'blank', 1, false) ?>
        </table>
    <? endif ?>
    <?= $form->getFormStart(URLHelper::getLink('dispatch.php/literature/edit_element?_catalog_id=' . $catalog_id),
        array('class' => 'studip-form', 'data-dialog' => '')) ?>
    <h1><?= ($element->isNewEntry()) ? _("Neuer Eintrag") : _('Eintrag') ?></h1>


    <? if (!$element->isNewEntry()) : ?>
        <p>
            <?= sprintf(_('Anzahl an Referenzen für diesen Eintrag: %s'), (int)$element->reference_count) ?><br>
            <b><?= ($element->getValue('user_id') == 'studip') ? _('Systemeintrag:') : _('Eingetragen von:') ?></b><br>
            <?= ($element->getValue('user_id') == 'studip') ? _('Dies ist ein vom System generierter Eintrag.') : get_fullname($element->getValue("user_id"), 'full', true) ?>
            <br>
            <b><?= _('Letzte Änderung') ?>:</b><br>
            <?= strftime('%d.%m.%Y', $element->getValue('chdate')) ?>
        </p>
    <? endif ?>
    <p>
        <small>
            <?= sprintf(_('Alle mit einem Sternchen %s markierten Felder müssen ausgefüllt werden.'),
                '<span style="font-size:1.5em;color:red;font-weigth:bold;">*</span>') ?>
        </small>
    </p>

    <? foreach ($element->fields as $field_name => $field_detail) : ?>
        <? if ($field_detail['caption']) : ?>
            <section <?= ($field_detail['mandatory']) ? 'class="required"' : '' ?>>
                <label for="<?= $field_name ?>" class="caption">
                    <?= $field_detail['caption'] ?>
                    <?= $form->getFormFieldInfo($field_name) ?>
                </label>

                <?
                $element_attributes = $attributes[$form->form_fields[$field_name]['type']];
                if (!$element->isChangeable()) {
                    $attributes['readonly'] = 'readonly';
                    $attributes['disabled'] = 'disabled';
                }
                ?>
                <?= $form->getFormField($field_name, $element_attributes) ?>
                <? if ($field_name == 'lit_plugin') : ?>
                    <p>
                        <?= (($link = $element->getValue('external_link'))) ? formatReady('=) [Link zum Katalog]' . $link) : _('(Kein Link zum Katalog vorhanden.)') ?>
                    </p>
                <? endif ?>
            </section>
        <? endif ?>
    <? endforeach ?>


    <footer class="submit_wrapper" data-dialog-button="1">
        <?= CSRFProtection::tokenTag() ?>
        <? if ($element->isChangeable()) : ?>
            <?= $form->getFormButton('send') . ($element->isNewEntry() ? '' : $form->getFormButton('delete')) ?>
        <? elseif ($catalog_id != 'new_entry') : ?>
            <?= Studip\LinkButton::create(_('Kopie erstellen'), URLHelper::getURL('dispatch.php/literature/edit_element?cmd=clone_entry&_catalog_id=' . $catalog_id),
                array('title' => _('Eine Kopie dieses Eintrages anlegen'), 'data-dialog' => '')) ?>
        <? endif ?>
        <? if ($catalog_id != "new_entry") : ?>
            <?= Assets::img('blank.gif', array('size' => '15@28')) ?>
            <?= Studip\LinkButton::create(_('Verfügbarkeit'), URLHelper::getURL('dispatch.php/literature/edit_element?cmd=check_entry&_catalog_id=' . $catalog_id),
                array('title' => _('Verfügbarkeit überprüfen'), 'data-dialog' => '')) ?>
        <? endif ?>
        <? if ($catalog_id != "new_entry" && !$clipboard->isInClipboard($catalog_id)) : ?>
            <?= Assets::img('blank.gif', array('size' => '15@28')) ?>
            <?= Studip\LinkButton::create(_('Merkliste'), URLHelper::getURL('dispatch.php/literature/edit_element?cmd=in_clipboard&_catalog_id=' . $catalog_id),
                array('title' => _('Eintrag in Merkliste aufnehmen'), 'data-dialog' => '')) ?>
        <? endif ?>
    </footer>

    <?= $form->getFormEnd(); ?>
</section>
<? if ($reload && $return_range) : ?>
    <script>
        jQuery('#lit_edit_element').parent().dialog({
            beforeClose: function () {
                window.location.href = "<?= URLHelper::getURL('dispatch.php/literature/edit_list?_range_id='.$return_range, array(), true)?>";
            }
        });
    </script>
<? endif;