<?php
use Tygh\Registry;
if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        fn_trusted_vars('birth_date');
        $birth_date = $_POST['birth_date'];
        $age_verified = is_adult($birth_date);
        setcookie('age_verified', $age_verified === 'true' ? 'true' : 'false');

        return [CONTROLLER_STATUS_REDIRECT, 'index.php'];
        }
 
