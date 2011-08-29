<div style="padding:5px;border: 1px dotted">
    <h4><?= htmlReady($request->getTypeExplained()) ?></h4>
    <div>
        <?= _("Anfragender:") ?>
        <?= htmlReady($request['user_id'] ? get_fullname($request['user_id']) : '') ?>
    </div>
    <div>
        <?= _("Letzte Änderung:") ?>
        <?= htmlReady(strftime('%x',$request['chdate'])) ?>
    </div>
    <?= htmlReady($request->getInfo(),1,1); ?>
</div>
