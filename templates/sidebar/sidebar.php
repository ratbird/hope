<div id="layout-sidebar">
    <section class="sidebar">
<? if ($image): ?>
        <div class="sidebar-image <? if ($avatar) echo 'sidebar-image-with-context'; ?>">
            <img src="<?= Assets::image_path($image) ?>" alt="">
        <? if ($title): ?>
            <span class="sidebar-title"><?= htmlReady($title) ?></span>
        <? endif; ?>
        <? if ($avatar) : ?>
            <div class="sidebar-context">
                <?= $avatar->getImageTag(Avatar::MEDIUM) ?>
            </div>
        <? endif ?>
        </div>
<? endif; ?>

    <? foreach ($widgets as $index => $widget): ?>
        <?= $widget->render(array('base_class' => 'sidebar')) ?>
    <? endforeach; ?>
    </section>
</div>