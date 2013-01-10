<?= '<?xml version="1.0" encoding="utf-8"?>' ?>
<feed xmlns="http://www.w3.org/2005/Atom">
    <author>
        <name><?= htmlReady(studip_utf8encode(get_fullname($user_id))) ?></name>
    </author>
    <title>Blubber von <?= htmlReady(studip_utf8encode(get_fullname($user_id))) ?> auf <?= htmlReady($GLOBALS['UNI_NAME_CLEAN']) ?></title>
    <id><?= md5($GLOBALS['ABSOLUTE_URI_STUDIP']."/plugins.php/Blubber/forum/feed/".$user_id) ?></id>
    <updated><?= date("c") ?></updated>

    <? foreach ($postings as $posting) : ?>
    <entry>
        <title><?= htmlReady(studip_utf8encode(substr($posting['description'], 0, 32))) ?></title>
        <link rel="alternate" type="text/html" href="<?= $GLOBALS['ABSOLUTE_URI_STUDIP']."/plugins.php/Blubber/forum/thread/".$posting->getId() ?>"/>
        <id><?= $GLOBALS['ABSOLUTE_URI_STUDIP']."/plugins.php/Blubber/forum/thread/".$posting['root_id'] ?></id>
        <updated><?= date("c", $posting['chdate']) ?></updated>
        <content><?= htmlReady(studip_utf8encode($posting['description'])) ?></content>
        <activity:verb>http://activitystrea.ms/schema/1.0/post</activity:verb>
        <author>
            <name><?= htmlReady(studip_utf8encode($posting['user_id'])) ?></name>
            <uri><?= $GLOBALS['ABSOLUTE_URI_STUDIP']."/about.php?username=".get_username($posting['user_id']) ?></uri>
            <link rel="alternate" type="text/html" href="<?= $GLOBALS['ABSOLUTE_URI_STUDIP']."/about.php?username=".get_username($posting['user_id']) ?>"/>
            <link rel="avatar" type="image/png" media:width="200" media:height="250" href="<?= Avatar::getAvatar($posting['user_id'])->getURL(Avatar::NORMAL) ?>"/>
        </author>
        <link rel="ostatus:conversation" href="<?= $GLOBALS['ABSOLUTE_URI_STUDIP']."/plugins.php/Blubber/forum/thread/".$posting['root_id'] ?>"/>
    </entry>
    <? endforeach ?>
</feed>