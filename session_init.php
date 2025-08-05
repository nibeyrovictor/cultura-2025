<?php
require_once './db.php';
require_once './SessionHandlerMySQL.php';

$handler = new SessionHandlerMySQL($pdo);
session_set_save_handler($handler, true);
session_start();

?>