<?php

class activity_skin_score implements activity_skin{
    private $steps = [];
    private $weight = "";
    private $maincss = "";
    private $linearproportion = 0;

    static function get_editor_config(){
        return [
            "settings" => [
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
        
        // derive the normalised proportion value for linear score calculation
        $this->linearproportion = $this->settings["settings"]["linear-value-part"] / $total;
        
        // sort the steps and derive each of their proportion values for discrete score calculation
        $this->steps = asort($steps);
        $sum = 0;
        foreach($this->steps as &$step){
            $sum += $step["fixed-value-part"];
            $step["proportion"] = $sum / $total;
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
        $proportion = $scorefactor * $this->linearproportion + $beststep["proportion"];
        
        // return the result
        return [$beststep, $proportion];
    }
}
