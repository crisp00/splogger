<?php
require_once("splogger/splogger.php");

$config = new SploggerConfig();
$config->db_host = "localhost";
$config->db_user = "root";
$config->db_pass = "";
$config->db_database = "xx";
$config->db_prefix = "xx";
//$splogger = new Splogger($config, "pi_");
$splogger = new Splogger($config, "pi_");
$page = "login";
?>

<html>
    <head>
        <?php
            require("./templates/head.html");
        ?>
    </head>
    </body>
        <?php
            switch($page){
                case "login":
                    require("./templates/login.html");
                break;
            }
        ?>
    </body>
</html>