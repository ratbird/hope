<form action="<?= $controller->url_for('profilemodules/update', compact('username')) ?>" method="post">
    <?= CSRFProtection::tokenTag() ?>
    <table class="default nohover plus" id="profile_modules">
        <caption><?= _('Inhaltselemente') ?></caption>
        <tbody>

<?
    foreach ($sortedList as $category => $pluginlist) {
        if ($_SESSION['profile_plus']['displaystyle'] != 'category' && $category != 'Plugins und Module A-Z') continue;
        if (isset($_SESSION['profile_plus']) && !$_SESSION['profile_plus']['Kategorie'][$category] && $category != 'Plugins und Module A-Z') continue;
?>
        <tr>
            <th colspan = 3>
                <?= $category ?>
            </th>
        </tr>

    <?  foreach ($pluginlist as $key => $val) {

            $plugin=$val['object'];
            $info = $plugin->getMetadata();
            $pluginname = isset($info['displayname']) ? $info['displayname'] : $plugin->getPluginname();
            $URL = $plugin->getPluginURL();
            //if(isset($info['complexity']) && isset($_SESSION['profile_plus']) && !$_SESSION['profile_plus']['Komplex'][$info['complexity']])continue;
    ?>

        <tr class="<?= $pre_check != null ? ' quiet' : '' ?>">
            <td colspan = 3>

                <div class="plus_basic">

                    <!-- checkbox -->
                    <input type="checkbox" id="<?= $pluginname ?>" name="modules[]" value="<?= $plugin->getPluginId() ?>" <?= $val['activated'] ? 'checked' : '' ?>>

                    <div class="element_header">

                        <!-- Name -->
                        <label for="<?= $pluginname ?>"><strong><?= $pluginname ?></strong></label>

                        <!-- komplex -->
                        <? switch ($info['complexity']){
                                case 3: $complexname = 'Intensiv';
                                        break;
                                case 2: $complexname = 'Erweitert';
                                        break;
                                case 1: $complexname = 'Standard';
                                        break;
                                default: $complexname = 'Nicht angegeben';
                                        break;
                            }
                        ?>


                        <? if(isset($info['complexity'])) :
                            $color1 = isset($info['complexity']) ? "hsl(57, 100%, 50%)" : "hsl(0, 0%, 100%)";
                            $color2 = isset($info['complexity']) && $info['complexity']>1 ? "hsl(42, 100%, 50%)" : "hsl(0, 0%, 100%)";
                            $color3 = isset($info['complexity']) && $info['complexity']>2 ? "hsl(15, 100%, 50%)" : "hsl(0, 0%, 100%)";
                            $border_color1 = isset($info['complexity']) ? "hsl(57, 100%, 45%)" : "hsl(0, 0%, 80%)";
                            $border_color2 = isset($info['complexity']) && $info['complexity']>1 ? "hsl(42, 100%, 45%)" : "hsl(0, 0%, 80%)";
                            $border_color3 = isset($info['complexity']) && $info['complexity']>2 ? "hsl( 15, 100%, 45%)" : "hsl(0, 0%, 80%)";

                        ?>
                        <div class="complexity" title="Komplexität: <?= $complexname ?>">
                            <div class="complexity_element" style="background-color: <?= $color1?>; border-color: <?= $border_color1?>;"></div>
                            <div class="complexity_element" style="background-color: <?= $color2?>; border-color: <?= $border_color2?>;"></div>
                            <div class="complexity_element" style="background-color: <?= $color3?>; border-color: <?= $border_color3?>;"></div>
                        </div>
                        <? endif ?>

                    </div>

                    <div class="element_description">

                        <!-- icon -->
                        <? if(isset($info['icon'])) : ?>
                            <img class="plugin_icon" alt="" src="<?= $URL."/".$info['icon'] ?> ">
                        <? endif ?>

                        <!-- shortdesc -->
                        <strong class="shortdesc">
                        <?= formatReady($info['descriptionshort']) ?>
                        <? if (!isset($info['descriptionshort'])) : ?>
                            <? if (isset($info['summary'])) : ?>
                                <?= formatReady($info['summary']) ?>
                            <? endif ?>
                        <? endif ?>
                        </strong>

                    </div>

                </div>

              <? if ($_SESSION['profile_plus']['View'] == 'openall' || !isset($_SESSION['profile_plus'])){?>

                <div class="plus_expert">

                    <div class="screenshot_holder">
                        <? if (isset($info['screenshot'])) : 
                        	$fileext = end(explode(".", $info['screenshot']));
                        	$filename = str_replace("_"," ",basename($info['screenshot'], ".".$fileext));?>

                            <a href="<?= $URL."/".$info['screenshot'] ?>"
                               data-lightbox="<?= $pluginname ?>" data-title="<?= $filename ?>">
                               <img class="big_thumb" src="<?= $URL."/".$info['screenshot'] ?>" alt="<?= $pluginname ?>" />
                            </a>

                            <?
                            if(isset($info['additionalscreenshots'])){ ?>

                            <div class="thumb_holder">

                                <? for( $i=0; $i < count($info['additionalscreenshots']); $i++){ 
                                $fileext = end(explode(".", $info['additionalscreenshots'][$i]));
                                $filename = str_replace("_"," ",basename($info['additionalscreenshots'][$i], ".".$fileext));?>

                                <a href="<?= $URL."/". $info['additionalscreenshots'][$i] ?>"
                                   data-lightbox="<?= $pluginname ?>" data-title="<?= $filename ?>">
                                   <img class="small_thumb" src="<?= $URL."/". $info['additionalscreenshots'][$i] ?>" alt="<?= $pluginname ?>" />
                                </a>

                                <? } ?>

                            </div>

                            <? } ?>

                        <? endif ?>
                    </div>

                    <div class="descriptionbox">

                        <!-- inhaltlöschenbutton -->
                        <? if(method_exists($plugin, 'deleteContent')) echo LinkButton::create(_('Inhalte löschen'), URLHelper::getURL("?deleteContent=true&name=".$key), array('style'=>'float:right; z-index: 1;')); ?>

                        <!-- tags -->
                        <? if (isset($info['keywords'])) : ?>
                        <ul class="keywords">
                        <? foreach(explode(';',$info['keywords']) as $keyword){
                            echo '<li>'.$keyword.'</li>'; }?>
                        </ul>
                        <? endif ?>

                        <!-- longdesc -->
                        <? if (isset($info['descriptionlong'])) : ?>
                            <p class="longdesc">
                            <?=  formatReady($info['descriptionlong']) ?>
                            </p>
                        <? endif ?>

                        <? if (!isset($info['descriptionlong'])) : ?>
                            <p class="longdesc">
                            <? if (isset($info['description'])) : ?>
                                <?= formatReady($info['description']) ?>
                            <? else: ?>
                                <?= _("Für dieses Element ist keine Beschreibung vorhanden.") ?>
                            <? endif ?>
                            </p>
                        <? endif ?>


                        <? if (isset($info['homepage'])) : ?>
                            <p>
                                <strong><?= _('Weitere Informationen:') ?></strong>
                                <a href="<?= htmlReady($info['homepage']) ?>"><?= htmlReady($info['homepage']) ?></a>
                            </p>
                        <? endif ?>

                        <!-- helplink -->
                        <? if (isset($info['helplink'])) : ?>
                        <a class="helplink" href=" <?= formatReady($info['helplink']) ?> ">...mehr</a>
                        <? endif ?>

                    </div>
                </div>
                <? } ?>
            </td>
        </tr>
    <? }
    } ?>





        </tbody>
        <tfoot>
            <tr>
                <td colspan="3">
                    <?= Studip\Button::createAccept(_('Übernehmen'), 'submit') ?>
                </td>
            </tr>
        </tfoot>
    </table>
</form>
