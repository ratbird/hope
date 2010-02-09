<h4><?= $heading ?></h4>
<ol>
<? foreach($lines as $line) : ?>
    <? 
        switch($type){
            case "seminar":
                $link = URLHelper::getLink('details.php', array('sem_id' => $line["seminar_id"],
                                                                'send_from_search' => 'true',
                                                                'send_from_search_page' => $view));
                break;
            case "user":
                $link = URLHelper::getLink('about.php', array('username' => $line["username"]));
                break;
            default:
                $link = $view;
        }
    ?>
    <li>
        <a href="<?= $link ?>"><?= htmlReady($line['display']) ?></a> (<?=$line['count']?>)
    </li>
<? endforeach ?>
</ol>
