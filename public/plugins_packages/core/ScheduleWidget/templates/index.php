<div class="schedule_widget_wrapper">
<div style="text-align: center; font-weight: bold; font-size: 1.2em">
    <? if($inst_mode) : ?>
    <?= $institute_name  ?>: <?= _('Stundenplan im') ?>
    <? else : ?>
    <?= _('Mein Stundenplan im') ?>
    <? endif ?>
    <?= $current_semester['name'] ?>
</div>


<?

?>
<?= $calendar_view->render(array('show_hidden' => $show_hidden)) ?>

<?//= $this->render_partial('_entry.php'); ?>
<?//= $this->render_partial('_entry_details') ?>
</div>
