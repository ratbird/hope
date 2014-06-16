<section class="document-dialog">
    <aside>
    <? if ($icon): ?>
        <?= Assets::img($icon) ?>
    <? endif; ?>
    </aside>
    <div>
        <?= $content_for_layout ?>
    </div>
</section>
