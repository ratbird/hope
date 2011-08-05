<div class="messagebox messagebox_warning">
    <?= sprintf(_('Die Veranstaltung wird in der Ansicht für %s angezeigt. '.
        'Sie können die Ansicht %shier zurücksetzen%s.'),
        get_title_for_status($changed_status, 2),
        '<a href="'.URLHelper::getLink('dispatch.php/course/change_view').'">', '</a>');
    ?>
</div>
