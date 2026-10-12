<?php require_once 'config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($page_title ?? 'Recipe Hub') ?> | Recipe Hub</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<header class="site-header">
  <div class="container nav">
    <a href="index.php" class="logo">🍲 Recipe Hub</a>
    <nav>
      <a href="index.php">Browse</a>
      <?php if (isset($_SESSION['user_id'])): ?>
        <a href="add_recipe.php">Share Recipe</a>
        <a href="my_recipes.php">My Recipes</a>
        <a href="logout.php">Logout (<?= e($_SESSION['username']) ?>)</a>
      <?php else: ?>
        <a href="login.php">Login</a>
        <a href="register.php" class="btn-small">Sign Up</a>
      <?php endif; ?>
    </nav>
  </div>
</header>
<main class="container">
