<?php
$page_title = "Browse Recipes";
require 'header.php';

$q    = trim($_GET['q'] ?? '');
$cat  = $_GET['category'] ?? '';
$sort = $_GET['sort'] ?? 'new';

$sql = "SELECT r.*, u.username, COALESCE(AVG(v.rating),0) AS avg_rating, COUNT(v.id) AS total_reviews
        FROM recipes r
        JOIN users u ON u.id = r.user_id
        LEFT JOIN reviews v ON v.recipe_id = r.id
        WHERE 1";
$params = [];

if ($q !== '') {
    $sql .= " AND (r.title LIKE ? OR r.ingredients LIKE ? OR r.description LIKE ?)";
    $like = "%$q%";
    array_push($params, $like, $like, $like);
}
if ($cat !== '') {
    $sql .= " AND r.category = ?";
    $params[] = $cat;
}
$sql .= " GROUP BY r.id ORDER BY " . ($sort === 'rating' ? "avg_rating DESC, total_reviews DESC" : "r.created_at DESC");

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$recipes = $stmt->fetchAll();
?>

<div class="hero">
  <h1>Discover &amp; Share Delicious Recipes</h1>
  <p>Search by name or ingredient, rate your favourites, and share your own.</p>
  <form method="GET" class="search-bar">
    <input type="text" name="q" placeholder="Search recipes or ingredients..." value="<?= e($q) ?>">
    <select name="category">
      <option value="">All Categories</option>
      <?php foreach ($categories as $c): ?>
        <option value="<?= e($c) ?>" <?= $cat === $c ? 'selected' : '' ?>><?= e($c) ?></option>
      <?php endforeach; ?>
    </select>
    <select name="sort">
      <option value="new" <?= $sort === 'new' ? 'selected' : '' ?>>Newest</option>
      <option value="rating" <?= $sort === 'rating' ? 'selected' : '' ?>>Top Rated</option>
    </select>
    <button type="submit">Search</button>
  </form>
</div>

<?php if (!$recipes): ?>
  <div class="empty"><h2>No recipes found</h2><p>Try a different search, or <a href="add_recipe.php">share the first one!</a></p></div>
<?php else: ?>
  <div class="grid">
    <?php foreach ($recipes as $r): ?>
      <div class="card">
        <a href="recipe.php?id=<?= $r['id'] ?>">
          <?php if ($r['image_url']): ?>
            <img src="<?= e($r['image_url']) ?>" alt="<?= e($r['title']) ?>" referrerpolicy="no-referrer">
          <?php else: ?>
            <div class="img-placeholder">🍽️</div>
          <?php endif; ?>
        </a>
        <div class="card-body">
          <span class="tag"><?= e($r['category']) ?></span>
          <h3><a href="recipe.php?id=<?= $r['id'] ?>"><?= e($r['title']) ?></a></h3>
          <div class="stars"><?= stars($r['avg_rating']) ?>
            <span class="meta">(<?= number_format($r['avg_rating'], 1) ?> &middot; <?= $r['total_reviews'] ?> reviews)</span></div>
          <p class="meta">by <?= e($r['username']) ?></p>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php require 'footer.php'; ?>
