<?php
session_start();

$host = "127.0.0.1";
$db   = "recipe_db";
$user = "root";
$pass = "";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

$categories = ["Breakfast", "Lunch", "Dinner", "Dessert", "Snack", "Drink", "Vegetarian"];

function e($s) { return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

function stars($n) {
    $n = (int) round($n);
    return str_repeat('★', $n) . str_repeat('☆', 5 - $n);
}

function require_login() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }
}
