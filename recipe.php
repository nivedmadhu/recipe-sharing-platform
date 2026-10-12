<?php
require_once 'config.php';

$id = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT r.*, u.username FROM recipes r JOIN users u ON u.id = r.user_id WHERE r.id = ?");
$stmt->execute([$id]);
$recipe = $stmt->fetch();
if (!$recipe) { http_response_code(404); die("Recipe not found. <a href='index.php'>Go back</a>"); }

$isOwner = isset($_SESSION['user_id']) && $_SESSION['user_id'] == $recipe['user_id'];
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_login();

    // Delete recipe (owner only)
    if (($_POST['action'] ?? '') === 'delete' && $isOwner) {
        $pdo->prepare("DELETE FROM recipes WHERE id = ? AND user_id = ?")->execute([$id, $_SESSION['user_id']]);
        header("Location: my_recipes.php");
        exit;
    }

    // Add or update review
    if (($_POST['action'] ?? '') === 'review') {
        $rating = (int) ($_POST['rating'] ?? 0);
        $comment = trim($_POST['comment'] ?? '');
        if ($isOwner) {
            $error = "You cannot review your own recipe.";
        } elseif ($rating < 1 || $rating > 5) {
            $error = "Please choose a rating from 1 to 5.";
        } else {
            $pdo->prepare("INSERT INTO reviews (recipe_id, user_id, rating, comment) VALUES (?,?,?,?)
                           ON DUPLICATE KEY UPDATE rating = VALUES(rating), comment = VALUES(comment), created_at = NOW()")
                ->execute([$id, $_SESSION['user_id'], $rating, $comment]);
            $message = "Thanks! Your review has been saved.";
        }
    }
}

$stmt = $pdo->prepare("SELECT COALESCE(AVG(rating),0) AS avg_rating, COUNT(*) AS total FROM reviews WHERE recipe_id = ?");
$stmt->execute([$id]);
$stats = $stmt->fetch();

$stmt = $pdo->prepare("SELECT v.*, u.username FROM reviews v JOIN users u ON u.id = v.user_id
                       WHERE v.recipe_id = ? ORDER BY v.created_at DESC");
$stmt->execute([$id]);
$reviews = $stmt->fetchAll();

$myReview = null;
if (isset($_SESSION['user_id'])) {
    foreach ($reviews as $rv) if ($rv['user_id'] == $_SESSION['user_id']) $myReview = $rv;
}

$page_title = $recipe['title'];
require 'header.php';
?>

<div class="panel">
  <div class="recipe-top">
    <?php if ($recipe['image_url']): ?>
      <img src="<?= e($recipe['image_url']) ?>" alt="<?= e($recipe['title']) ?>" referrerpolicy="no-referrer">
    <?php endif; ?>
    <div class="recipe-info">
      <span class="tag"><?= e($recipe['category']) ?></span>
      <h1><?= e($recipe['title']) ?></h1>
      <p class="meta">Shared by <strong><?= e($recipe['username']) ?></strong> on <?= date('M j, Y', strtotime($recipe['created_at'])) ?></p>
      <p class="stars"><?= stars($stats['avg_rating']) ?>
        <span class="meta"><?= number_format($stats['avg_rating'], 1) ?>/5 (<?= $stats['total'] ?> reviews)</span></p>
      <p style="margin-top:10px"><?= e($recipe['description']) ?></p>

      <?php if ($isOwner): ?>
      <div class="actions">
        <a class="btn btn-orange" href="add_recipe.php?id=<?= $id ?>">Edit</a>
        <form method="POST" onsubmit="return confirm('Delete this recipe permanently?');">
          <input type="hidden" name="action" value="delete">
          <button type="submit" class="btn-red">Delete</button>
        </form>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<div class="panel">
  <h2>Ingredients</h2>
  <p class="recipe-text"><?= e($recipe['ingredients']) ?></p>
</div>

<div class="panel">
  <h2>Instructions</h2>
  <p class="recipe-text"><?= e($recipe['instructions']) ?></p>
</div>

<div class="panel">
  <h2>Rate &amp; Review</h2>
  <?php if ($message): ?><div class="alert alert-success"><?= e($message) ?></div><?php endif; ?>
  <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

  <?php if (!isset($_SESSION['user_id'])): ?>
    <p><a href="login.php">Login</a> to leave a review.</p>
  <?php elseif (!$isOwner): ?>
    <form method="POST">
      <input type="hidden" name="action" value="review">
      <label>Your Rating</label>
      <select name="rating" required>
        <option value="">-- Select --</option>
        <?php for ($i = 5; $i >= 1; $i--): ?>
          <option value="<?= $i ?>" <?= ($myReview && $myReview['rating'] == $i) ? 'selected' : '' ?>><?= $i ?> - <?= stars($i) ?></option>
        <?php endfor; ?>
      </select>
      <label>Your Review</label>
      <textarea name="comment" style="min-height:90px"><?= e($myReview['comment'] ?? '') ?></textarea>
      <button type="submit"><?= $myReview ? "Update Review" : "Submit Review" ?></button>
    </form>
  <?php else: ?>
    <p class="meta">You can't review your own recipe.</p>
  <?php endif; ?>
</div>

<div class="panel">
  <h2>Reviews (<?= $stats['total'] ?>)</h2>
  <?php if (!$reviews): ?>
    <p class="meta">No reviews yet. Be the first!</p>
  <?php endif; ?>
  <?php foreach ($reviews as $rv): ?>
    <div class="review">
      <strong><?= e($rv['username']) ?></strong>
      <span class="stars"><?= stars($rv['rating']) ?></span>
      <span class="meta"><?= date('M j, Y', strtotime($rv['created_at'])) ?></span>
      <?php if ($rv['comment']): ?><p><?= nl2br(e($rv['comment'])) ?></p><?php endif; ?>
    </div>
  <?php endforeach; ?>
</div>

<?php require 'footer.php'; ?>
