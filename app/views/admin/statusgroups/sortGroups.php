<noscript>
    <?= MessageBox::info(_('Leider ist es aus technischen Gr�nden nicht m�glich ein vern�nftiges Interface '
                         . 'ohne Javascript zu liefern. Nutzen sie bitte die Gruppierung unter den '
                         . 'Einstellungen der Gruppen oder aktivieren sie Javascript.')) ?>
</noscript>

<div class="ordering" title="<?= _('Gruppenreihenfolge �ndern') ?>">
    <div class="nestable">
        <?= $this->render_partial('admin/statusgroups/_group-nestable.php', compact('groups')) ?>
    </div>
</div>

<form class="studip_form" id="order_form" action="<?= $controller->url_for('admin/statusgroups/sortGroups') ?>" method="POST">
    <input type="hidden" name="ordering" id="ordering">
    
    <div data-dialog-button>
        <?= Studip\Button::createAccept(_('Speichern'), 'order') ?>
        <?= Studip\LinkButton::createCancel(_('Abbrechen'), $controller->url_for('admin/statusgroups/index')) ?>
    </div>
</form>
