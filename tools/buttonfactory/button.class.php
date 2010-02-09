<?php
class button {
	var $template = ""; // Template of the button
	var $path = ""; // path to store the button
	var $button_id = ""; //id of the button (part of the filename)
	var $buttontext = "";   //label of the button
	var $offset = ""; // pixel offset for buttons with additional images on the left/right
	var $font = "";
	var $fontsize = 8.5;
        
function button($button_id, $buttontext, $path, $font, $img = "button.png", $offset = 0) {
		$this->button_id = $button_id;
		$this->template = $this->Loadpng ($img);
		$this->buttontext = $buttontext;
		$this->path = $path;
		$this->font = $font;
		$this->offset = round((imagesx($this->template) - $this->get_linewidth($this->buttontext))/2)+1-$offset;
		}

function Loadpng ($imgname) {
    $im = @ImageCreateFromPNG ($imgname); /* Versuch, Datei zu öffnen */
    if (!$im) {                           /* Prüfen, ob fehlgeschlagen */
        $im = ImageCreate (150, 30);      /* Erzeugen eines leeren Bildes */
        $bgc = ImageColorAllocate ($im, 255, 255, 255);
        $tc  = ImageColorAllocate ($im, 0, 0, 0);
        ImageFilledRectangle ($im, 0, 0, 150, 30, $bgc);
        /* Ausgabe einer Fehlermeldung */
        ImageString($im, 1, 5, 5, "Fehler beim Öffnen von: $imgname", $tc);
    } 
    return $im;
}

function get_linewidth($line) {
		$coordinates = imagettfbbox ( $this->fontsize, 0, $this->font, $line);
		return ($coordinates[4] - $coordinates[0]);
	}

function RenderButton($direct_out = false) {
		$black = ImageColorAllocate ($this->template, 20, 20, 20);
		ImageTTFText ($this->template, $this->fontsize, 0, $this->offset, 14, $black, $this->font, $this->buttontext);
		if(!$direct_out) ImagePNG ($this->template, $this->path.$this->button_id."-button.png");
		else ImagePNG ($this->template);
		ImageDestroy ($this->template);
		}
}
?>
