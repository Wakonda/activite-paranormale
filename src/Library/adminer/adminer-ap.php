<?php

function adminer_object() { 
    include_once "plugins/AdminerPlugin.php";
    include_once "plugins/FillLoginForm.php";

    $plugins = [
        new FillLoginForm("server", $_ENV["DB_HOST"], $_ENV["DB_USER"], $_ENV["DB_PASSWORD"], $_ENV["DB_NAME"])
    ];

    return new AdminerPlugin($plugins);
}

ob_start();
include "adminer.php";
ob_end_flush();