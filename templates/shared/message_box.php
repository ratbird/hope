<div class="messagebox messagebox_<?= $class ?>">
    <div class="messagebox_buttons">
        <? if (sizeof($details) && $close_details) : ?>
        <a href="#" onclick="Effect.toggle($(this).up().next('.messagebox_details'), 'blind'); $(this).select('img').each(Element.toggle); return false;">
            <?= Assets::img('icons/maximize_inv.png', array('alt' => _('Details anzeigen'), 'title' => _('Details anzeigen'))) ?>
            <?= Assets::img('icons/minimize_inv.png', array('alt' => _('Details ausblenden'), 'title' => _('Details ausblenden'), 'style'=>'display: none;')) ?>
        </a>
        <? endif ?>
        <a href="#" onclick="$(this).up('.messagebox').fade(); return false;">
            <?= Assets::img('icons/cross_inv.png', array('alt' => 'close', 'title' => _('Nachrichtenbox schließen'))) ?>
        </a>
    </div>
    <?= $message ?>
    <? if (sizeof($details)) : ?>
    <div class="messagebox_details"<? if ($close_details) : ?> style="display: none;" <? endif ?>>
        <ul>
            <? foreach ($details as $li) : ?>
                <li><?= $li ?></li>
            <? endforeach ?>
        </ul>
    </div>
    <? endif ?>
</div>
