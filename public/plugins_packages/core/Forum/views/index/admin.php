<style>
#forum a.tooltip2 {
  color: black;
  cursor: help;
  display: inline-block;
  outline: none;
  position: relative;
  text-decoration: none;
}
#forum a.tooltip2 span {
  position: relative;
  display: inline-block;
  margin-bottom: 9px;
  background-color: rgba(255, 255, 255, 0.3);
  background-image: -moz-linear-gradient(top, rgba(255, 255, 255, 0.5), rgba(255, 255, 255, 0));
  background-image: -webkit-gradient(linear, 0 0, 0 100%, from(rgba(255, 255, 255, 0.5)), to(rgba(255, 255, 255, 0)));
  background-image: -webkit-linear-gradient(top, rgba(255, 255, 255, 0.5), rgba(255, 255, 255, 0));
  background-image: -o-linear-gradient(top, rgba(255, 255, 255, 0.5), rgba(255, 255, 255, 0));
  background-image: linear-gradient(to bottom, rgba(255, 255, 255, 0.5), rgba(255, 255, 255, 0));
  background-repeat: repeat-x;
  filter: progid:DXImageTransform.Microsoft.gradient(startColorstr='#80ffffff', endColorstr='#00ffffff', GradientType=0);
  background-color: #ddd;
  border: 2px solid #ccc;
  -webkit-border-radius: 4px;
  -moz-border-radius: 4px;
  border-radius: 4px;
  top: 20%;
  bottom: none;
  -webkit-box-shadow: 0 1px 2px rgba(0, 0, 0, 0.4), 0 1px 0 rgba(255, 255, 255, 0.5) inset;
  -moz-box-shadow: 0 1px 2px rgba(0, 0, 0, 0.4), 0 1px 0 rgba(255, 255, 255, 0.5) inset;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.4), 0 1px 0 rgba(255, 255, 255, 0.5) inset;
  font-size: 10pt;
  font-weight: normal;
  margin-left: 0px;
  opacity: .95;
  padding: 10px;
  position: absolute;
  text-align: left;
  text-shadow: 0 1px 0 rgba(255, 255, 255, 0.4);
  visibility: hidden;
  white-space: normal;
  width: 400px;
  z-index: 999;
  clear: both;
}

#forum a.tooltip2:hover span {
  visibility: visible;
}
#forum li.selected {
    background-color: #DDDDFF;
}
</style>
<div id="forum">
<? foreach ($list as $category_id => $entries) : ?>
    <b><?= htmlReady($categories[$category_id]) ?></b><br>
    <?= $this->render_partial('index/_admin_entries', compact('entries')) ?>
<? endforeach ?>
</div>