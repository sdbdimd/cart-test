<?php 
 if (!defined('BOOTSTRAP')) { die('Access denied'); }

function is_adult($birth_date) { 
    $age = calculate_age($birth_date);
    return ($age >= 18) ? 'true' : 'false';
}
function calculate_age($birth_date) {
    $birthdate_timestamp = strtotime($birth_date);
    $birthdate = date('Y-m-d', $birthdate_timestamp);
    $today = date('Y-m-d');
    $age = date_diff(date_create($birthdate), date_create($today));
    return $age->y;
}
