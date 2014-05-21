<div class="accordion">
    <? foreach ($topics as $topic) : ?>
    <h2><?= htmlReady($topic['title']) ?></h2>
    <div>
        <table>
            <tbody>
                <tr>
                    <td><strong><?= _("Beschreibung") ?></strong></td>
                    <td><?= formatReady($topic['description']) ?></td>
                </tr>
                <tr>
                    <td><strong><?= _("Termine") ?></strong></td>
                    <td>
                        <ul>
                            <? foreach ($topic->dates as $date) : ?>
                            <li>
                                <a href="<?= URLHelper::getLink("dispatch.php/course/dates/details/".$date->getId()) ?>" data-dialog="buttons=false">
                                    <?= (floor($date['date'] / 86400) !== floor($date['end_time'] / 86400)) ? date("d.m.Y, H:i", $date['date'])." - ".date("d.m.Y, H:i", $date['end_time']) : date("d.m.Y, H:i", $date['date'])." - ".date("H:i", $date['end_time']) ?>
                                </a>
                            </li>
                            <? endforeach ?>
                        </ul>
                    </td>
                </tr>
                <tr>
                    <td><strong><?= _("Materialien") ?></strong></td>
                    <td>
                        <ul>
                            <li>
                                <?= _("Dateiordner") ?>
                            </li>
                            <li>
                                <?= _("Thema im Forum") ?>
                            </li>
                        </ul>
                    </td>
                </tr>
            </tbody>
        </table>
        <div style="text-align: center;">
            <a href="<?= URLHelper::getLink("dispatch.php/course/topics/edit/".$topic->getId()) ?>" data-dialog="buttons=false">
            <?= \Studip\Button::create(_("bearbeiten"), null, array()) ?>
            </a>
        </div>
    </div>
    <? endforeach ?>
</div>

<script>
    jQuery(function () {
        jQuery(".accordion").accordion();
    })
</script>

<?php
$sidebar = Sidebar::get();
$sidebar->setImage(Assets::image_path("sidebar/date-sidebar.png"));