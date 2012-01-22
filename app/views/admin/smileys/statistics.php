<dl class="smiley-statistics">
    <dt><?= _('Vorhanden:') ?></dt>
    <dd><?= $count_all ?></dd>
    
    <dt><?= _('Davon benutzt:') ?></dt>
    <dd><?= $count_used ?></dd>
    
    <dt><?= _('Smiley-Vorkommen:') ?></dt>
    <dd><?= $sum ?></dd>

    <dt><?= _('Letzte Änderung:') ?></dt>
    <dd><?= date('d.m.Y H:i:s', $last_change) ?></dd>
</dl>
