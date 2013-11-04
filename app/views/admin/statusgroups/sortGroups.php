<div style='display: none' id="order_div" title="<?= _('Gruppenreihenfolge ändern') ?>">
    <div class="dd">
        <? createLi($groups) ?>
    </div>
</div>

<?

function createLi($item) {
    ?>
    <ol class="dd-list">
    <? foreach ($item as $group): ?>
            <li class="dd-item" data-id="<?= $group->id ?>">
                <div class="dd-handle"><?= formatReady($group->name) ?></div>
            <? createLi($group->children); ?>
            </li>
    <? endforeach; ?>
    </ol>
    <?
}
?>

<form class="studip_form" action="<?= $controller->url_for('admin/statusgroups') ?>" method="POST">
    <input type='text' name='ordering' id='ordering' value='<?= var_dump($groups) ?>'>
<?= Studip\Button::create(_('Speichern'), 'order') ?>
</form>

<script>
    $('#ordering').hide();
    $('#order_div').show();
    $('.dd').nestable({});
    $('form').submit(function() {
        $('#ordering').val(JSON.stringify($('.dd').nestable('serialize')));
    });

</script>