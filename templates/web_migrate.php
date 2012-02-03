<?
# Lifter010: TODO
use Studip\Button, Studip\LinkButton;
?>
<?=$this->render_partial('header');?>
<div align="center">
  <table style="width: 80%;">
    <tr>
      <td class="topic">
        <?= Assets::img('icons/16/white/info.png') ?>
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
          <table class="steel1" width="100%">
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
                    <?= htmlspecialchars($migration->description()) ?>
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
          <form method="POST">
            <?= CSRFProtection::tokenTag() ?>
            <? if (isset($target)): ?>
              <input type="hidden" name="target" value="<?= $target ?>">
            <? endif ?>
            <div align="center">
              <?= Button::createAccept(_('Starten'), 'start')?>
            </div>
          </form>
        <? endif ?>
      </td>
    </tr>
  </table>
</div>
