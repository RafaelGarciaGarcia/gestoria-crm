<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>DEBUG POST</h1><hr>";

echo "<h2>MÉTODO DE ENVÍO:</h2>";
var_dump($_SERVER['REQUEST_METHOD']);

echo "<h2>POST RECIBIDO:</h2>";
echo "<pre>";
var_dump($_POST);
echo "</pre>";

echo "<h2>GET RECIBIDO:</h2>";
echo "<pre>";
var_dump($_GET);
echo "</pre>";

echo "<h2>FILES RECIBIDO:</h2>";
echo "<pre>";
var_dump($_FILES);
echo "</pre>";