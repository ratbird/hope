<? if (isset($flash['delete'])): ?>
    <?= createQuestion(_('Wollen Sie das Banner wirklich l�schen?'),
                       array('delete' => 1),
                       array('back' => 1),
                       $controller->url_for('admin/banner/delete', $flash['delete']['banner_id'])) ?>
<? endif; ?>

<h3><?= _('Verwaltung von Werbebannern') ?></h3>
<table class="default">
    <thead>
        <tr>
            <th><?= _('Banner') ?></th>
            <th><?= _('Beschreibung') ?></th>
            <th><?= _('Typ') ?></th>
            <th><?= _('Ziel') ?></th>
            <th><?= _('Zeitraum') ?></th>
            <th><?= _('Klicks') ?></th>
            <th><?= _('Views') ?></th>
            <th><?= _('Prio') ?></th>
            <th><?= _('Aktionen') ?></th>
        </tr>
    </thead>
    <tbody>
    <? foreach ($banners as $banner): ?>
        <tr>
            <td style="text-align: center;">
                <?= $banner->toImg(array('style' => 'max-width: 80px')) ?>
            </td>
            <td><?= htmlReady($banner['description']) ?></td>
            <td><?= $banner['target_type']?></td>
            <td>
                <? if ($banner['target_type'] == 'seminar'): ?>
                    <?= mila(reset(get_object_name($banner['target'], 'sem')),30) ?>
                <? elseif ($banner['target_type'] == 'inst') :?>
                    <?= mila(reset(get_object_name($banner['target'], 'inst')),30) ?>
                <? else: ?>
                    <?= $banner['target'] ?>
                <? endif; ?>
            </td>
            <td style="text-align: center;">
                <?= $banner['startdate'] ? date("d.m.Y", $banner['startdate']) : _("sofort") ?><br>
                <?= _("bis") ?><br>
                <?= $banner['enddate'] ? date("d.m.Y", $banner['enddate']) : _("unbegrenzt") ?>
            </td>
            <td align="center">
                <?= number_format($banner['clicks'], 0, ',', '.') ?>
            </td>
            <td align="center">
                <?= number_format($banner['views'], 0, ',', '.') ?>
            </td>
            <td><?= $banner['priority'] ?> (<?= $banner->getViewProbability() ?>)</td>
            <td style="text-align: right;">
                <a class="load-in-new-row" href="<?= $controller->url_for('admin/banner/info',  $banner["ad_id"]) ?>?path=<?= urlencode($banner['banner_path']) ?>">
                    <?= Assets::img('icons/16/blue/info', array('title' => _('Eigenschaften'))) ?>
                </a>
                <a href="<?= $controller->url_for('admin/banner/edit', $banner["ad_id"]) ?>?path=<?= urlencode($banner['banner_path']) ?>">
                    <?= Assets::img('icons/16/blue/edit', array('title' => _('Banner bearbeiten'))) ?>
                </a>
                <a href="<?= $controller->url_for('admin/banner/reset', $banner['ad_id']) ?>">
                    <?= Assets::img('icons/16/blue/refresh', array('title' => _('Klicks/Views zur�cksetzen'))) ?>
                </a>
                <a href="<?= $controller->url_for('admin/banner/delete', $banner['ad_id']) ?>">
                    <?= Assets::img('icons/16/blue/trash', array('title' => _('Banner l�schen'))) ?>
                </a>
            </td>
        </tr>
    <? endforeach; ?>
    </tbody>
</table>