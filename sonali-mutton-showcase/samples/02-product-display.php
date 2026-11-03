<?php
/**
 * ============================================================================
 * Portfolio Code Sample: Safe Product Rendering & Bilingual Localization
 * Project: Sonali Mutton Shop
 * Author:  Aawez Irfan Sayyed
 * Notice:  Sanitized portfolio extract demonstrating presentation layer security
 *          and dynamic Marathi/English localization.
 * ============================================================================
 */

declare(strict_types=1);

/**
 * Returns localized database column value based on active language session.
 *
 * If Marathi ('mr') is selected and a translated field exists (e.g. 'name_mr'),
 * it returns the translated string; otherwise it falls back to the default English column.
 *
 * @param array<string, mixed> $row   Database result row
 * @param string               $field Base column name (e.g., 'name', 'description')
 * @param string               $lang  Current active language code ('en' | 'mr')
 * @return string
 */
function getLocalizedField(array $row, string $field, string $lang = 'en'): string
{
    if ($lang === 'mr') {
        $mrField = $field . '_mr';
        if (!empty($row[$mrField])) {
            return (string)$row[$mrField];
        }
    }
    return (string)($row[$field] ?? '');
}

/**
 * Formats a numeric price into localized Indian Rupee currency string.
 *
 * @param float|int|string $amount
 * @return string
 */
function formatCurrency($amount): string
{
    return '₹' . number_format((float)$amount, 2);
}

/**
 * Renders a single product card markup with safe output escaping.
 *
 * Demonstrates:
 * 1. Output escaping using htmlspecialchars() with ENT_QUOTES and UTF-8
 * 2. Proper numeric casting
 * 3. Fallback placeholders for missing product imagery
 * 4. Bilingual badge and unit displays
 *
 * @param array<string, mixed> $product Database record of the mutton cut/item
 * @param string               $lang    Current language ('en' | 'mr')
 * @return string Safe HTML card markup
 */
function renderProductCard(array $product, string $lang = 'en'): string
{
    $id          = (int)($product['id'] ?? 0);
    $name        = getLocalizedField($product, 'name', $lang);
    $description = getLocalizedField($product, 'description', $lang);
    $price       = (float)($product['price'] ?? 0.0);
    $unit        = (string)($product['unit'] ?? 'kg');
    $category    = getLocalizedField($product, 'cat_name', $lang);
    $inStock     = !empty($product['is_available']);

    // Image sanitization with safe fallback
    $rawImage = (string)($product['image'] ?? '');
    $imageSrc = (!empty($rawImage) && preg_match('/^[a-zA-Z0-9_\-\.]+\.(jpg|jpeg|png|webp)$/i', $rawImage))
        ? 'assets/images/' . $rawImage
        : 'assets/images/default_product.jpg';

    // Localized labels
    $addToCartLabel = ($lang === 'mr') ? 'खरेदी करा' : 'Add to Cart';
    $outOfStockText = ($lang === 'mr') ? 'उपलब्ध नाही' : 'Out of Stock';

    ob_start();
    ?>
    <article class="product-card" data-product-id="<?= $id ?>">
        <div class="product-media">
            <img src="<?= htmlspecialchars($imageSrc, ENT_QUOTES, 'UTF-8') ?>"
                 alt="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>"
                 loading="lazy"
                 class="product-thumb">
            <?php if (!$inStock): ?>
                <span class="stock-badge out-of-stock"><?= htmlspecialchars($outOfStockText, ENT_QUOTES, 'UTF-8') ?></span>
            <?php endif; ?>
        </div>

        <div class="product-body">
            <span class="product-category"><?= htmlspecialchars($category, ENT_QUOTES, 'UTF-8') ?></span>
            <h3 class="product-title"><?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?></h3>
            <p class="product-desc"><?= htmlspecialchars(mb_strimwidth($description, 0, 95, '...'), ENT_QUOTES, 'UTF-8') ?></p>
            
            <div class="product-meta">
                <span class="product-price">
                    <strong><?= formatCurrency($price) ?></strong>
                    <small>/ <?= htmlspecialchars($unit, ENT_QUOTES, 'UTF-8') ?></small>
                </span>

                <?php if ($inStock): ?>
                    <button type="button"
                            class="btn btn-add-cart"
                            data-action="add-cart"
                            data-id="<?= $id ?>">
                        <i class="fas fa-shopping-basket"></i>
                        <span><?= htmlspecialchars($addToCartLabel, ENT_QUOTES, 'UTF-8') ?></span>
                    </button>
                <?php else: ?>
                    <button class="btn btn-disabled" disabled><?= htmlspecialchars($outOfStockText, ENT_QUOTES, 'UTF-8') ?></button>
                <?php endif; ?>
            </div>
        </div>
    </article>
    <?php
    return (string)ob_get_clean();
}
