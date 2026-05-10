<?php
$feedbackProductName = $feedbackProductName ?? '';
$feedbackRedirect = $feedbackRedirect ?? basename($_SERVER['PHP_SELF']);
$feedbackProductKey = md5(strtolower($feedbackProductName));
$feedbackSaved = ($_GET['review'] ?? '') === 'saved';
$feedbackInvalid = ($_GET['review'] ?? '') === 'invalid';
$feedbackItems = [];
$feedbackAverage = 0;
$feedbackCount = 0;

$pdo->exec(
    "CREATE TABLE IF NOT EXISTS product_feedback (
        id INT AUTO_INCREMENT PRIMARY KEY,
        product_key VARCHAR(32) NOT NULL,
        product_name VARCHAR(255) NOT NULL,
        user_email VARCHAR(255) NOT NULL,
        user_name VARCHAR(255) NOT NULL,
        rating TINYINT NOT NULL,
        comment TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_product_user (product_key, user_email)
    )"
);

if ($feedbackProductName !== '') {
    $summaryStmt = $pdo->prepare("SELECT AVG(rating) AS avg_rating, COUNT(*) AS rating_count FROM product_feedback WHERE product_key = ?");
    $summaryStmt->execute([$feedbackProductKey]);
    $feedbackSummary = $summaryStmt->fetch(PDO::FETCH_ASSOC) ?: [];
    $feedbackAverage = (float)($feedbackSummary['avg_rating'] ?? 0);
    $feedbackCount = (int)($feedbackSummary['rating_count'] ?? 0);

    $itemsStmt = $pdo->prepare("SELECT user_name, rating, comment, updated_at FROM product_feedback WHERE product_key = ? ORDER BY updated_at DESC LIMIT 12");
    $itemsStmt->execute([$feedbackProductKey]);
    $feedbackItems = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
}

function renderFeedbackStars(float $rating): string {
    $rounded = (int)round($rating);
    $stars = '';

    for ($i = 1; $i <= 5; $i++) {
        $stars .= $i <= $rounded ? '★' : '☆';
    }

    return $stars;
}
?>
<main class="review-section">
  <div class="review-heading">
    <div>
      <h2>Avis des clients</h2>
      <p><?= $feedbackCount > 0 ? number_format($feedbackAverage, 1) . '/5 - ' . (int)$feedbackCount . ' avis' : 'Aucun avis pour le moment' ?></p>
    </div>
    <div class="review-stars" aria-label="Note moyenne"><?= renderFeedbackStars($feedbackAverage) ?></div>
  </div>

  <?php if ($feedbackSaved): ?>
    <p class="review-message success">Merci, votre avis a ete enregistre.</p>
  <?php elseif ($feedbackInvalid): ?>
    <p class="review-message warning">Choisissez une note entre 1 et 5.</p>
  <?php endif; ?>

  <?php if ($currentUser): ?>
    <form action="../Backend/product-feedback.php" method="post" class="review-form">
      <input type="hidden" name="product_name" value="<?= htmlspecialchars($feedbackProductName) ?>">
      <input type="hidden" name="redirect" value="<?= htmlspecialchars($feedbackRedirect) ?>">

      <div class="rating-picker" aria-label="Votre note">
        <?php for ($star = 5; $star >= 1; $star--): ?>
          <input type="radio" id="rating-<?= (int)$star ?>" name="rating" value="<?= (int)$star ?>" required>
          <label for="rating-<?= (int)$star ?>" title="<?= (int)$star ?> etoiles">★</label>
        <?php endfor; ?>
      </div>

      <textarea name="comment" placeholder="Partagez votre avis sur ce produit..." maxlength="1000"></textarea>
      <button type="submit" class="review-submit">Envoyer l'avis</button>
    </form>
  <?php else: ?>
    <p class="review-login"><a href="../Backend/login/Login.php">Connectez-vous</a> pour laisser une note et un commentaire.</p>
  <?php endif; ?>

  <div class="reviews-list">
    <?php if (empty($feedbackItems)): ?>
      <p class="review-empty">Soyez le premier a partager votre experience.</p>
    <?php else: ?>
      <?php foreach ($feedbackItems as $feedback): ?>
        <article class="review-item">
          <div class="review-item-header">
            <strong><?= htmlspecialchars($feedback['user_name']) ?></strong>
            <span><?= renderFeedbackStars((float)$feedback['rating']) ?></span>
          </div>
          <?php if (trim($feedback['comment'] ?? '') !== ''): ?>
            <p><?= nl2br(htmlspecialchars($feedback['comment'])) ?></p>
          <?php endif; ?>
        </article>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</main>
