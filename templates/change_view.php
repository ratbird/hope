<div class="messagebox messagebox_warning">
    <?= Icon::create('visibility-checked', 'info', ['title' => _('Studierendenansicht aktiv')])->asImg() ?>
    <?= sprintf(_('Die Veranstaltung wird in der Ansicht f�r %s angezeigt. '.
        'Sie k�nnen die Ansicht %shier zur�cksetzen%s.'),
        get_title_for_status($changed_status, 2),
        '<a href="'.URLHelper::getLink('dispatch.php/course/change_view').'">', '</a>');
    ?>
</div>
