<?
# Lifter010: TODO
use Studip\Button, Studip\LinkButton;

?>
<? if (isset($flash['error'])): ?>
    <?= MessageBox::error($flash['error'], $flash['error_detail']) ?>
<? elseif (isset($flash['message'])): ?>
    <?= MessageBox::info($flash['message']) ?>
<? elseif (isset($flash['success'])): ?>
    <?= MessageBox::success($flash['success'], $flash['success_detail']) ?>
<? elseif (isset($flash['delete'])): ?>
    <?= createQuestion(_('Wollen Sie die Zuordnung der Veranstaltung zum automatischen Eintragen wirklich löschen?'),  array('delete' => 1), array('back' => 1), $controller->url_for('admin/autoinsert/delete') .'/'. $flash['delete']) ?>
<? endif; ?>

<h2>
    <?= _('Automatisches Eintragen von Erstnutzern in Veranstaltungen') ?>
</h2>
<h3>
    <?= _('Suche nach Veranstaltungen')?>
</h3>

<form action="<?= $controller->url_for('admin/autoinsert') ?>" method="post">
    <?= CSRFProtection::tokenTag() ?>
    <?= $this->render_partial("admin/autoinsert/_search.php", array('semester_data' => $semester_data)) ?>
</form>

<? if (count($seminar_search) > 0): ?>
<form action="<?= $controller->url_for('admin/autoinsert/new') ?>" method="post">
    <?= CSRFProtection::tokenTag() ?>
    <table class="default">
        <thead>
            <tr>
                <th colspan="2"><?= _('Suchergegbnisse') ?></th>
            </tr>
        </thead>
        <tbody>
            <tr class="<?= TextHelper::cycle('hover_odd', 'hover_even') ?>">
                <td width="30%">
                    <label for="sem_id"><?= _('Veranstaltung:') ?></label>
                </td>
                <td>
                   <select name="sem_id" id="sem_id">
                   <? foreach ($seminar_search as $seminar): ?>
                        <option value="<?= $seminar[0] ?>">
                            <?= htmlReady($seminar[1]) ?>
                        </option>
                   <? endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr class="<?= TextHelper::cycle('hover_odd', 'hover_even') ?>">
                <td>
                    <?= _('Automatisches Eintragen mit Nutzerstatus:') ?>
                </td>
             
              
            </tr>
                  <?php foreach($userdomains as $domain):?>
              <tr class="<?= TextHelper::cycle('cycle_odd', 'cycle_even') ?>">
                  <td> <?= htmlReady($domain['name']) ?></td>
                   <td> <label>
                        <input type="checkbox" name="rechte[<?= $domain['id']?>][]" value="dozent">
                        <?= _('Dozent') ?>
                    </label>
                    <label>
                        <input type="checkbox" name="rechte[<?= $domain['id']?>][]" value="tutor">
                        <?= _('Tutor') ?>
                    </label>
                    <label>
                        <input type="checkbox" name="rechte[<?= $domain['id']?>][]" value="autor">
                        <?= _('Autor') ?>
                    </label>
                </td>
                    <?php endforeach;?>
            </tr>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2" align="center">
                    <?= Button::create(_('Anlegen'),'anlegen')?>
                </td>
            </tr>
        </tfoot>
    </table>
</form>
<? endif;?>

<h3><?= _('Vorhandene Zuordnungen') ?></h3>
<table width="100%" class="default">
    <thead>
        <tr>
            <th><?= _('Veranstaltungen') ?></th>
            <th style="text-align: center;"><?= _('Dozent') ?></th>
            <th style="text-align: center;"><?= _('Tutor') ?></th>
            <th style="text-align: center;"><?= _('Autor') ?></th>
            <th style="text-align: right;"><?= _('Aktionen') ?></th>
        </tr>
    </thead>
    <tbody>
    <? foreach ($auto_sems as $auto_sem): ?>
        <tr class="<?= TextHelper::cycle('hover_odd', 'hover_even') ?>">
            <td>
                <a href="<?= URLHelper::getLink('seminar_main.php?auswahl='.$auto_sem['seminar_id']) ?>">
                    <?= htmlReady($auto_sem['Name'])?>
                </a>
            </td>
                
           
         
            
        
            <?= $this->render_partial("admin/autoinsert/_status.php", array('status' => 'dozent', 'auto_sem' => $auto_sem,'domains'=>$userdomains)) ?>
            <?= $this->render_partial("admin/autoinsert/_status.php", array('status' => 'tutor', 'auto_sem' => $auto_sem,'domains'=>$userdomains)) ?>
            <?= $this->render_partial("admin/autoinsert/_status.php", array('status' => 'autor', 'auto_sem' => $auto_sem,'domains'=>$userdomains)) ?>
            <td align="right">
                <a href="<?=$controller->url_for('admin/autoinsert/delete')?>/<?= $auto_sem['seminar_id'] ?>">
                    <?= Assets::img('icons/16/blue/trash.png', array('title' => _('Veranstaltung entfernen'), 'class' => 'text-top')) ?>
                </a>
            </td>
        </tr>
        <? $i ++?>
    <? endforeach; ?>
    </tbody>
</table>

<?
$sidebar = Sidebar::Get();
$sidebar->setTitle('Automatisiertes Eintragen');
$links = new ActionsWidget();
$links->addLink(_('Benutzergruppen manuell eintragen'), $controller->url_for('admin/autoinsert/manual'), 'icons/16/blue/visibility-visible.png');
$sidebar->addWidget($links);