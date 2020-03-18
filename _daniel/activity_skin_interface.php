<?php

interface activity_skin{
    /*
    * Get the configuration structure for the editor to use
    */
    static function get_editor_config();
    
    /*
    * Apply setting set
    *
    * $settings is an associative array of tableName => table
    *   where each table is a no-associative array of table rows
    *   where each table row is an associative array of propertyName => propertyValue
    * by convention the table named "settings", only includes exactly one row
    *
    * returns NULL on success, or an error object on error
    *
    * NOTE : It is the caller's responsibility to ensure that:
    *   
    */
    function apply_settings($settings, $weight);
    
    /*
    * get the proportion value and achievement text to pass thriugh to the section renderer
    * note: The value received by the section renderer will be evaluated by the caller to this
    * method by multiplying the weight from the skin settings by the proportion value provided here
    *   $score is the score from the grade book (typically a vqlue between 0 and 20 in France)
    *   $scorefactor is the factor from the grade book (a value between 0 and 1)
    *   $achievement is the achievement value from the Moodle achievements system
    */
    function get_user_results(float $score, float $scorefactor, string $achievement);

    /*
    * populate the tile object for rendering
    *   $score is the score from the grade book (typically a vqlue between 0 and 20 in France)
    *   $scorefactor is the factor from the grade book (a value between 0 and 1)
    *   $achievement is the achievement value from the Moodle achievements system
    */
    function populate_tile(float $score, float $scorefactor, string $achievement, skinned_tile $tile);
}
