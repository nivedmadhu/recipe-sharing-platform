<?php
require_once 'config.php';
require_login();

$id = (int) ($_GET['id'] ?? 0);
$recipe = ['title'=>'','description'=>'','category'=>'','ingredients'=>'','instructions'=>'','image_url'=>''];

// Edit mode: load recipe and make sure the current user owns it
if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM recipes WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $_SESSION['user_id']]);
    $recipe = $stmt->fetch();
    if (!$recipe) { header("Location: my_recipes.php"); exit; }
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $recipe = [
        'title' => trim($_POST['title'] ?? ''),
        'description' => trim($_POST['description'] ?? ''),
        'category' => $_POST['category'] ?? '',
        'ingredients' => trim($_POST['ingredients'] ?? ''),
        'instructions' => trim($_POST['instructions'] ?? ''),
        'image_url' => trim($_POST['image_url'] ?? ''),
    ];

    if ($recipe['title'] === '') $errors[] = "Title is required.";
    if (!in_array($recipe['category'], $categories)) $errors[] = "Choose a valid category.";
    if ($recipe['ingredients'] === '') $errors[] = "Ingredients are required.";
    if ($recipe['instructions'] === '') $errors[] = "Instructions are required.";
    if ($recipe['image_url'] !== '' && !filter_var($recipe['image_url'], FILTER_VALIDATE_URL))
        $errors[] = "Image URL is not valid.";

    if (!$errors) {
        if ($id) {
            $pdo->prepare("UPDATE recipes SET title=?, description=?, category=?, ingredients=?, instructions=?, image_url=?
                           WHERE id=? AND user_id=?")
                ->execute([$recipe['title'], $recipe['description'], $recipe['category'], $recipe['ingredients'],
                           $recipe['instructions'], $recipe['image_url'], $id, $_SESSION['user_id']]);
        } else {
            $pdo->prepare("INSERT INTO recipes (user_id, title, description, category, ingredients, instructions, image_url)
                           VALUES (?,?,?,?,?,?,?)")
                ->execute([$_SESSION['user_id'], $recipe['title'], $recipe['description'], $recipe['category'],
                           $recipe['ingredients'], $recipe['instructions'], $recipe['image_url']]);
            $id = $pdo->lastInsertId();
        }
        header("Location: recipe.php?id=$id");
        exit;
    }
}

$page_title = $id ? "Edit Recipe" : "Share a Recipe";
require 'header.php';
?>
<div class="panel form-box wide">
  <h1><?= $id ? "Edit Recipe" : "Share Your Recipe" ?></h1>
  <?php foreach ($errors as $err): ?><div class="alert alert-error"><?= e($err) ?></div><?php endforeach; ?>
  <form method="POST">
    <label>Recipe Title</label>
    <input type="text" name="title" value="<?= e($recipe['title']) ?>" required>

    <label>Category</label>
    <select name="category" required>
      <option value="">-- Select --</option>
      <?php foreach ($categories as $c): ?>
        <option value="<?= e($c) ?>" <?= $recipe['category'] === $c ? 'selected' : '' ?>><?= e($c) ?></option>
      <?php endforeach; ?>
    </select>

    <label>Short Description</label>
    <textarea name="description" style="min-height:70px"><?= e($recipe['description']) ?></textarea>

    <label>Image URL (optional)</label>
    <input type="url" name="image_url" placeholder="https://..." value="<?= e($recipe['image_url']) ?>">

    <label>Ingredients (one per line)</label>
    <textarea name="ingredients" required><?= e($recipe['ingredients']) ?></textarea>

    <label>Instructions (one step per line)</label>
    <textarea name="instructions" required><?= e($recipe['instructions']) ?></textarea>

    <button type="submit"><?= $id ? "Update Recipe" : "Publish Recipe" ?></button>
  </form>
</div>
<?php require 'footer.php'; ?>
