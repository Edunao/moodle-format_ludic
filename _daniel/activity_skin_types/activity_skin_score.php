<?php

class activity_skin_score implements activity_skin{
    private $steps = [];
    private $weight = "";
    private $maincss = "";
    private $linearproportion = 0;

    static function get_editor_config(){
        return [
            "settings" => [
                "name"                  => "text",
                "main-css"              => "css",
                "linear-value-part"     => "int",
            ],
            "steps" => [
                "score-threshold"       => "number",
                "fixed-value-part"      => "int",
                "step-image"            => "image",
                "step-text"             => "string",
                "step-css"              => "css"
            ]
        ];
    }

    //2000 pts
    //1000 points linéaires
    //500 pts 70%
    //500 pts 100%

    // linear-value-part => 2

    // steps

    // 1 :
    // -- threshold : 0
    // -- fixed-value-part : 0

    // 2 :
    // -- threshold : 70
    // -- fixed-value-part : 1

    // 3 :
    // -- threshold : 100
    // -- fixed-value-part : 1


    function apply_settings($settings, $weight){
        // copy out static settings
        $this->maincss = $settings["settings"]["main-css"];
        $this->weight = $weight;
        
        // copy steps into an associative array, indexed by threshold and calulate the total value parts score
        $steps  = [];
        $total  = $settings["settings"]["linear-value-part"];
        foreach($settings["steps"] as $step){
            $total += $step["fixed-value-part"];
            $threshold = $step["score-threshold"];
            $steps[$threshold] = $step;
        }
        // $total => 4
        
        // derive the normalised proportion value for linear score calculation
        $this->linearproportion = $this->settings["settings"]["linear-value-part"] / $total;

        // $this->linearproportion =>  0.5

        // sort the steps and derive each of their proportion values for discrete score calculation
        $this->steps = asort($steps);
        $sum = 0;
        foreach($this->steps as &$step){
            $sum += $step["fixed-value-part"];
            $step["proportion"] = $sum / $total;
            // step 1 : proportion = 0 / 4 = 0
            // step 2 : proportion = 1 / 4 = 0.25
            // step 3 : proportion = 2 / 4 = 0.5

        }
    }
    
    function get_user_results(float $score, float $scorefactor, string $achievement){
        list($step, $proportion) = $this->derive_state($score);
        return [
            "proportion"    => $proportion,
            "achievement"   => $achievement
        ];
    }

    function populate_tile(array $skinsettings, float $score, float $scorefactor, string $achievement, skinned_tile $tile){
        list($step, $proportion) = $this->derive_state($score);
        $value = $proportion * $this->weight;
        
        // setup core tile properties
        $tile->add_skin_image($step["step-image"]);
        $tile->add_skin_text($step["step-text"], "step-text");
        $tile->add_css_rules($this->maincss);
        $tile->add_css_rules($step["step-css"]);
        
        // add text relating to advancement
        $tile->add_skin_text(
            '<span class="current-value">' . $value . '</span>' .
            '<span class="value-seaparator">/</span>' . 
            '<span class="max-value">' . $this->weight . '</span>',
            "full-value");
        $tile->add_skin_text($value, "current-value");
        $tile->add_skin_text($this->weight, "max-value");
        
        // add css class to indicate general state of advancement
        $tile->add_css_class($achievement);
    }
    
    private function derive_state(float $score){
        // setup a default step object to use when no better match is available
        $beststep = [
            "score-threshold"       => 0,
            "fixed-value-part"      => 0,
            "step-image"            => "",
            "step-text"             => "",
            "step-css"              => ""
        ];
        
        // identify the best match for the provided score
        foreach($this->steps as $step){
            if ($step["score-threshold"] <= $score){
                $beststep = $step;
            }
        }
        
        // derive the proportion value for the score
        $scorefactor = $score / $scoremax;
        // proportion note. note / note max
        // step 1 : $scorefactor >= 0.0 && < 0.7 : proportionfinale = ($scorefactor * 0.5) + 0
        // step 2 : $scorefactor >= 0.7 && < 1.0 : proportionfinale = ($scorefactor * 0.5) + 0.25
        // step 3 : $scorefactor >= 1.0 : proportionfinale = ($scorefactor * 0.5) + 0.5
        $proportion = $scorefactor * $this->linearproportion + $beststep["proportion"];


        // return the result
        return [$beststep, $proportion];
    }


    // score de section
    // recupère le score direct de toutes les activités
    // le tout / sur le poids max.
}
