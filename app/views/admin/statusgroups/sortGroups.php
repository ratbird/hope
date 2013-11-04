<noscript><?= _('Leider ist es aus technischen Gr�nden nicht m�glich ein vern�nftiges Interface ohne Javascript zu liefern. Nutzen sie bitte die Gruppierung unter den Einstellungen der Gruppen oder aktivieren sie Javascript.') ?></noscript>

<div class='ordering' style='display: none'>
    <div  id="order_div" title="<?= _('Gruppenreihenfolge �ndern') ?>">
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

    <form class="studip_form" id='order_form' action="<?= $controller->url_for('admin/statusgroups') ?>" method="POST">
        <input type='hidden' name='ordering' id='ordering'>
        <?= Studip\Button::create(_('Speichern'), 'order') ?>
    </form>
</div>

<script>
    $('.ordering').show();
    $('.dd').nestable({});
    $('#order_form').submit(function() {
        $('#ordering').val(JSON.stringify($('.dd').nestable('serialize')));
    });

</script>