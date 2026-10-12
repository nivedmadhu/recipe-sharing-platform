<?php
require_once 'config.php';
require_login();
$page_title = "My Recipes";
require 'header.php';

$stmt = $pdo->prepare("SELECT r.*, COALESCE(AVG(v.rating),0) AS avg_rating, COUNT(v.id) AS total_reviews
                       FROM recipes r LEFT JOIN reviews v ON v.recipe_id = r.id
                       WHERE r.user_id = ? GROUP BY r.id ORDER BY r.created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$recipes = $stmt->fetchAll();
?>
<h1>My Recipes</h1>
<?php if (!$recipes): ?>
  <div class="empty"><p>You haven't shared any recipes yet.</p><br><a class="btn" href="add_recipe.php">Share your first recipe</a></div>
<?php else: ?>
  <div class="grid">
    <?php foreach ($recipes as $r): ?>
      <div class="card">
        <?php if ($r['image_url']): ?><img src="<?= e($r['image_url']) ?>" alt="" referrerpolicy="no-referrer"><?php else: ?><div class="img-placeholder">🍽️</div><?php endif; ?>
        <div class="card-body">
          <span class="tag"><?= e($r['category']) ?></span>
          <h3><a href="recipe.php?id=<?= $r['id'] ?>"><?= e($r['title']) ?></a></h3>
          <div class="stars"><?= stars($r['avg_rating']) ?> <span class="meta">(<?= $r['total_reviews'] ?>)</span></div>
          <div class="actions"><a class="btn btn-orange" href="add_recipe.php?id=<?= $r['id'] ?>">Edit</a></div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
<?php require 'footer.php'; ?>
