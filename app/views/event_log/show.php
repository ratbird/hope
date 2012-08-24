<?
# Lifter010: TODO
use Studip\Button, Studip\LinkButton;

?>
<h3><?= _('Anzeige der Log-Events') ?></h3>

<form action="<?= $controller->url_for('event_log/show') ?>" method="POST">
  <?= CSRFProtection::tokenTag() ?>
  <p style="font-size: smaller;">
    <select name="action_id">
      <option value="all"><?= _('Alle Aktionen') ?></option>
      <? foreach ($log_actions as $log_action): ?>
        <option value="<?= $log_action['action_id'] ?>"
        <? if ($log_action['action_id'] === $action_id): ?>
          selected
        <? endif ?>
        <? if ($log_action['log_group'] !== $lastgroup): ?>
          <? $lastgroup = $log_action['log_group'] ?>
          style="border-top: 1px solid #cccccc;"
        <? endif ?>
        >
          <?= htmlReady($log_action['description']) ?>
        </option>
      <? endforeach ?>
    </select>

    <?= _('für') ?>

    <? if (isset($objects)): ?>
      <? foreach ($types as $name => $title): ?>
        <? if ($type === $name): ?>
          <?= htmlReady($title) ?>
        <? endif ?>
      <? endforeach ?>

      <input type="hidden" name="type" value="<?= htmlReady($type) ?>">
      <input type="hidden" name="search" value="<?= htmlReady($search) ?>">

      <select name="object_id">
        <? foreach ($objects as $object): ?>
          <? $selected = $object[0] === $object_id ? 'selected' : '' ?>
          <option value="<?= $object[0] ?>" <?= $selected ?>><?= htmlReady($object[1]) ?></option>
        <? endforeach ?>
      </select>

      <a href="<?= $controller->url_for('event_log/show?action_id='.urlencode($action_id)) ?>">
        <?= Assets::img('icons/16/blue/refresh.png', array('title' => _('neue Suche'))) ?>
      </a>
    <? else: ?>
      <select name="type">
        <? foreach ($types as $name => $title): ?>
          <option value="<?= $name ?>"><?= htmlReady($title) ?></option>
        <? endforeach ?>
      </select>

      <input type="text" size="20" name="search">
    <? endif ?>

    <?= _('in') ?>

    <select name="format">
      <option value="compact"><?= _('Kompaktdarstellung') ?></option>
      <option value="detail"><?= _('Detaildarstellung') ?></option>
    </select>

    &nbsp;
    <?= Button::create(_('Anzeigen')) ?>
  </p>

  <? if (isset($error_msg)): ?>
    <?= MessageBox::error($error_msg) ?>
  <? endif ?>

  <? if (isset($log_events)): ?>
    <table class="default">
      <tr>
        <th>
          <?= _('Zeit') ?>
        </th>
        <th>
          <?= _('Info') ?>
        </th>
      </tr>

      <? foreach ($log_events as $log_event): ?>
        <tr class="<?= TextHelper::cycle('table_row_even', 'table_row_odd') ?>">
          <td style="font-size: smaller; white-space: nowrap;">
            <?= date('d.m.Y H:i:s', $log_event['time']) ?>
          </td>
          <td style="font-size: smaller;">
            <?= $log_event['info'] ?>
            <? if ($format === 'detail' && $log_event['detail']): ?>
              <br><?= _('Info:').' '.$log_event['detail'] ?>
            <? endif ?>
            <? if ($format === 'detail' && $log_event['debug']): ?>
              <br><?= _('Debug:').' '.$log_event['debug'] ?>
            <? endif ?>
          </td>
        </tr>
      <? endforeach ?>
    </table>

    <p>
      <? if (count($log_events) > 0): ?>
        <?= sprintf(_('Eintrag %s - %s von %s'), $start + 1, $start + count($log_events), $num_entries) ?>

        <input type="hidden" name="start" value="<?= $start ?>">

        <? if ($start > 0): ?>
          <?= Button::create('<< '. _("Zurück"), 'back') ?>
        <? endif ?>
        <? if ($start + count($log_events) < $num_entries): ?>
          <?= Button::create(_('Weiter') . " >>", 'forward') ?>
        <? endif ?>
    <? else: ?>
      <?= _('keine Einträge gefunden') ?>
    <? endif ?>
    </p>
  <? endif ?>

</form>
