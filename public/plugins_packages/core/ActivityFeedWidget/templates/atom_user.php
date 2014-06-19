<?= '<?xml version="1.0" encoding="UTF-8"?>' ?>

<feed xmlns="http://www.w3.org/2005/Atom" xml:base="<?= htmlspecialchars($base_url) ?>">
    <id>urn:studip:<?= md5(Request::url()) ?></id>
    <title>
        <?= htmlspecialchars(studip_utf8encode($title)) ?>
    </title>
    <subtitle><?= utf8_encode(_('Neueste Aktivitäten')) ?></subtitle>
    <link rel="alternate" href="<?= htmlspecialchars($base_url) ?>"/>
    <link rel="self" href="<?= htmlspecialchars(Request::url()) ?>"/>
    <updated><?= date('c', $updated) ?></updated>
    <author>
        <name>
            <?= htmlspecialchars(utf8_encode($author_name)) ?>
        </name>
        <email>
            <?= htmlspecialchars($author_email) ?>
        </email>
    </author>

    <? foreach ($items as $item): ?>
        <entry>
            <id>urn:studip:<?= $item['id'] ?></id>
            <title>
                <?= htmlspecialchars(studip_utf8encode($item['title'])) ?>
            </title>
            <author>
                <name>
                    <?= htmlspecialchars(studip_utf8encode($item['author'])) ?>
                </name>
            </author>
            <link href="<?= $item['link'] ?>"/>
            <updated><?= date('c', $item['updated']) ?></updated>
            <summary type="html">
                <?= htmlspecialchars(studip_utf8encode($item['summary'])) ?>
            </summary>
            <category term="<?= $item['category'] ?>"/>
        </entry>
    <? endforeach ?>
</feed>
