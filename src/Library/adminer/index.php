<?php
$_ENV["USER"]="barney";
ob_start();

include("adminer-ap.php");

ob_get_clean();