<div style="padding:5px;border: 1px dotted">
    <h4><?= htmlReady($request->getTypeExplained()) ?></h4>
    <p>
        <?= _("Anfragender:") ?>
        <?= htmlReady($request['user_id'] ? get_fullname($request['user_id']) : '') ?>
    </p>
    <? if ($request['chdate']) : ?>
        <p>
        <?= _("Letzte Änderung:") . ' ' . htmlReady(strftime('%x',$request['chdate'])); ?>
        </p>
    <? endif ?>
    <p>
    <?= htmlReady($request->getInfo(),1,1); ?>
    </p>
</div>
