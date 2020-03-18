<?php

interface section_skin(){
    /*
    * Get the configuration structure for the editor to use
    */
    function get_editor_config();

    /*
    * Apply setting set
    *
    * $settings is an associative array of tableName => table
    *   where each table is a no-associative array of table rows
    *   where each table row is an associative array of propertyName => propertyValue
    */
    function apply_settings($settings);
    
    /*
    * populate the tile object for rendering
    *
    * $userdata is an associative array of activityId => activityData
    *   where activity data is an associative array including: { "value"=>..., "proportion"=>..., "achievement"=>... }
    *   where both the value integer and achievement string are provided by the activity skin
    */
    function populate_tile(array $userdata, skinned_tile $tile);
}
