<?
# Lifter010: TODO
use Studip\Button, Studip\LinkButton;
?>
<?=$this->render_partial('header');?>
<?=$message?>
<div align="center">
  <table style="width: 80%;">
    <tr>
      <td class="table_header_bold">
        <?= Icon::create('info', 'info_alt')->asImg() ?>
        <b>
          <?= _('Stud.IP Web-Migrator') ?>
        </b>
      </td>
    </tr>
    <tr>
      <td class="blank" style="padding: 1ex;">
        <p>
          Aktueller Versionsstand: <?= $current ?>
        </p>
        <? if (empty($migrations)): ?>
          <p>
            <?= _('Ihr System befindet sich auf dem aktuellen Stand.') ?>
          </p>
        <? else: ?>
          <p>
            <?= _('Die hier aufgeführten Anpassungen werden beim Klick auf "starten" ausgeführt:') ?>
          </p>
          <table class="table_row_even" width="100%">
            <tr>
              <th>
                <?= _('Nr.') ?>
              </th>
              <th>
                <?= _('Name') ?>
              </th>
              <th>
                <?= _('Beschreibung') ?>
              </th>
            </tr>
            <? foreach ($migrations as $number => $migration): ?>
              <tr>
                <td style="text-align: center;">
                  <?= $number ?>
                </td>
                <td>
                  <?= get_class($migration) ?>
                </td>
                <td>
                  <? if ($migration->description()): ?>
                    <?= htmlReady($migration->description()) ?>
                  <? else: ?>
                    <i>
                      <?= _('keine Beschreibung vorhanden') ?>
                    </i>
                  <? endif ?>
                </td>
              </tr>
            <? endforeach ?>
          </table>
          <p></p>
          <? if ($lock->isLocked($lock_data)): ?>
            <?= MessageBox::info(sprintf(_('Die Migration wurde %s von %s bereits angestossen und läuft noch.'),
                                         reltime($lock_data['timestamp']),
                                         User::find($lock_data['user_id'])->getFullName()),
                                 array(sprintf(_('Sollte während der Migration ein Fehler aufgetreten sein, so können Sie ' .
                                                 'diese Sperre durch den unten stehenden Link oder das Löschen der Datei ' .
                                                 '<em>%s</em> auflösen.'), $lock->getFilename()))) ?>
            <?= Studip\LinkButton::create(_('Sperre aufheben'), URLHelper::getURL('?release_lock=1&target=' . @$target)) ?>
          <? else: ?>
            <form method="POST">
              <?= CSRFProtection::tokenTag() ?>
              <? if (isset($target)): ?>
                <input type="hidden" name="target" value="<?= $target ?>">
              <? endif ?>
              <div align="center">
                <?= Button::createAccept(_('Starten'), 'start')?>
              </div>
            </form>
          <? endif; ?>
        <? endif ?>
      </td>
    </tr>
  </table>
</div>
