<?php

class skinned_tile{
    private $itemid         = '';
    private $skintype       = '';
    private $viewtype       = '';
    private $title          = '';
    private $cssclasses     = '';
    private $skinimages     = [];
    private $skintexts      = [];
    private $skincssrules   = '';
    private $popups         = [];

    // Contructor
    // $viewtype parameter will be 'course', 'section', 'activity', etc. It will allow one to filter pop-ups and suchlike out from views where they don't belong
    function construct($itemid, $skintype, $viewtype){
        $this->itemid   = 'ludic-tile-' . $itemid;
        $this->skintype = $skintype;
        $this->viewtype = $viewtype;
    }

    // Add a CSS class for the tile's root div
    function add_css_class($classname){
        // ignore addition of empty classname
        if (! $classname){
            return;
        }
        // make sure class name is valid
        $cleanname = preg_replace('/[^a-zA-Z0-9]/', '-', $classname);
        $this->cssclasses .= ' ' . $classname;
    }
    
    // Give the tile a title
    function set_title($title){
        $this->title = $title;
    }
    
    // Add an image to the skinned part tile, with optional additional css classes to qualify it
    function add_skin_image($imageid, $cssclasses=""){
        $this->skinimages[] = (object)[
            "imageid"    => $imageid,
            "cssclasses" => $cssclasses
        ];
    }
    
    // Add a text to the skinned part of the image, with optional additional css classes to qualify it
    function add_skin_text($txt, $cssclasses=""){
        $this->skintexts[] = (object)[
            "txt"        => $txt,
            "cssclasses" => $cssclasses
        ];
    }
    
    // Add some css rules to the tile - the css rules will be qualified to apply only to the tile itself
    function add_css_rules($cssrules){
        $this->skincssrules[] .= $cssrules . "\n";
    }
    
    // Add a pop-up to the tile
    // $popupdata contains the data required by the JS pop-up rendere (or at least the inital data set as provided at page construction time)
    // $popupmethod contains the name of the JS class for the pop-up renderer
    function add_popup($popupdata, $popupmethod, $buttoncssclasses=""){
        $this->popups[] = (object)[
            "popupdata"        => $popupdata,
            "popupmethod"      => $popupmethod,
            "buttoncssclasses" => $buttoncssclasses
        ];
    }
    
    // Render the tile content
    function get_content(){
        $result = '';
        
        // open full-tile
        $rootcssclasses = "full-tile skin-type-" . $this->skintype . $this->cssclasses;
        $result .= '<div id="' . $this->itemid . '" class="' . $rootcssclasses . '">';
        
        // open skin sub-tile
        $result .= '<div class="sub-tile skin-tile">';
        
        // add images
        foreach($this->skinimages as $image){
            $imageurl = 'pix/' . $image->imageid;
            $imgstyle = "'background-image:url(\"$imageurl\")'";
            $result .= '<div class="img" style=' . $imgstyle . '></div>';
        }
        
        // add texts
        foreach($this->skintexts as $text){
            $result .= '<div class="txt">' . $text->txt . '</div>';
        }
        
        // close skin sub-tile
        $result .= '</div>';
        
        // add title sub-tile
        $result .= '<div class="sub-tile title-tile">';
        $result .= '<div class="txt">' . $this->title . '</div>';
        $result .= '</div>';
        
        // close full-tile
        $result .= '</div>';
        
    }
}
