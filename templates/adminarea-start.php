<? if ($display): ?>
<p class="info">
<? if ($refered_from_seminar): ?>
    <?= sprintf(_('Hier k�nnen Sie die Daten der Veranstaltung <b>%s</b> direkt bearbeiten.'), htmlReady($name)) ?>
<? else: ?>
    <?= sprintf(_('Sie haben die Veranstaltung <b>%s</b> vorgew�hlt. Sie k�nnen nun direkt die einzelnen Bereiche dieser Veranstaltung bearbeiten, indem Sie die entsprechenden Men�punkte w�hlen.'), htmlReady($name)) ?>
<? endif; ?>
</p>
<p class="info">
    <?= _('Wenn Sie eine andere Veranstaltung bearbeiten wollen, klicken Sie bitte auf <b>Veranstaltungen</b> um zum Auswahlmen� zur�ckzukehren.') ?>
</p>
<? endif; ?>
