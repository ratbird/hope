<?='<?xml'?> version="1.0"?>
<rss version="2.0" xmlns:dc="http://purl.org/dc/elements/1.1/">
    <channel>
        <title><?= htmlspecialchars(studip_utf8encode($title)) ?></title>
        <link><?= htmlspecialchars(studip_utf8encode($studip_url)) ?></link>
        <image>
            <url><?= Assets::image_path('logos/studipklein.gif') ?></url>
            <title><?= htmlspecialchars(studip_utf8encode($title)) ?></title>
            <link><?= htmlspecialchars(studip_utf8encode($studip_url)) ?></link>
        </image>
        <description><?= htmlspecialchars(studip_utf8encode($description)) ?></description>
        <lastBuildDate><?= date('r',$last_changed) ?></lastBuildDate>
        <generator><?= htmlspecialchars(studip_utf8encode('Stud.IP - ' . $GLOBALS['SOFTWARE_VERSION'])) ?></generator>";
<? foreach ($items as $id => $item): ?>
        <item>
            <title><?= htmlspecialchars(studip_utf8encode($item['topic'])) ?></title>
            <link><?= htmlspecialchars(studip_utf8encode(sprintf($item_url_fmt, $studip_url, $id))) ?></link>
            <description><![CDATA[<?= studip_utf8encode(formatready($item['body'], 1, 1)) ?>]]></description>
            <dc:contributor><![CDATA[<?= studip_utf8encode($item['author']) ?>]]></dc:contributor>
            <dc:date><?= gmstrftime('%Y-%m-%dT%H:%MZ', $item['date']) ?></dc:date>
            <pubDate><?= date('r', $item['date']) ?></pubDate>
        </item>
<? endforeach; ?>
    </channel>
</rss>
