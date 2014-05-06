<section class="contentbox">
    <header>
        <h1>
            <?= _('Veranstaltungen') ?>
        </h1>
    </header>

    <section>
    <? foreach ($seminare as $semester => $seminar) :?>
    <b><?= htmlReady($semester) ?></b><br>

        <? foreach ($seminar as $id => $inhalt) :?>
            <a href="<?= URLHelper::getLink('details.php', array('sem_id' => $id))?>">
                <?= htmlReady($inhalt) ?>
            </a><br>
        <?endforeach?>
    <?endforeach?>
    </section>
</section>