<?php
if(!defined('ROOT')) exit('No direct script access allowed');

if(!function_exists("fetchSecurityPolicy")) {
    
    function fetchSecurityPolicy($fetchKey, $privilegeLevel = "*") {
        
        return [];
    }
}
?>