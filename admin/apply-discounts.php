<?php
require_once __DIR__ . '/../config/admin_bootstrap.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../database/admin/discounts.php';

$discountId = (int) ($_GET['id'] ?? $_POST['discount_id'] ?? 0);
if ($discountId <= 0) {
    header('Location: ' . app_path('/admin/discounts.php'));
    exit;
}

$discount = admin_fetch_discount_row($discountId);
if (!$discount) {
    header('Location: ' . app_path('/admin/discounts.php'));
    exit;
}

if (strtolower((string) ($discount['discount_type'] ?? '')) === 'bundle') {
    $_SESSION['admin_discounts_flash'] = [
        'message' => 'Bundle discounts are assigned from Bundle Management, not Apply Discounts.',
        'type' => 'error',
    ];
    header('Location: ' . app_path('/admin/discounts.php'));
    exit;
}

$setFlash = static function (string $message, string $type = 'success'): void {
    $_SESSION['admin_apply_discounts_flash'] = [
        'message' => $message,
        'type' => $type,
    ];
};

$consumeFlash = static function (): ?array {
    $flash = $_SESSION['admin_apply_discounts_flash'] ?? null;
    unset($_SESSION['admin_apply_discounts_flash']);
    return is_array($flash) ? $flash : null;
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        admin_discount_save_conditions($discountId, $_POST);
        $setFlash('Discount targets saved successfully.');
        header('Location: ' . app_path('/admin/apply-discounts.php?id=' . $discountId));
        exit;
    } catch (Throwable $exception) {
        $setFlash($exception->getMessage(), 'error');
        header('Location: ' . app_path('/admin/apply-discounts.php?id=' . $discountId));
        exit;
    }
}

$flash = $consumeFlash();
$conditions = admin_fetch_discount_conditions_grouped($discountId);
$targets = admin_fetch_discount_apply_targets();
$categorySubMap = [];
foreach ($targets['sub_categories'] as $subCategory) {
    $categorySubMap[(int) $subCategory['category_id']][] = $subCategory;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include __DIR__ . '/head.php'; ?>
    <link rel="stylesheet" href="<?php echo htmlspecialchars(app_path('/admin/assets/css/apply-discounts.css')); ?>">
</head>
<body class="admin-page">
    <?php include __DIR__ . '/navbar.php'; ?>

    <main class="admin-content apply-discounts-page">
        <header class="apply-header">
            <a class="back-link" href="<?php echo htmlspecialchars(app_path('/admin/discounts.php')); ?>" aria-label="Back to discounts">
                <i class="fa-solid fa-chevron-left"></i>
            </a>
            <h1>Apply Discounts</h1>
        </header>

        <section class="apply-info">
            <div class="info-row">
                <span class="label">ID:</span>
                <span class="value" data-discount-id><?php echo htmlspecialchars((string) $discount['public_discount_id']); ?></span>
            </div>
            <div class="info-row">
                <span class="label">Title:</span>
                <span class="value"><?php echo htmlspecialchars((string) $discount['name']); ?></span>
            </div>
            <div class="info-row">
                <span class="label">Discount Type:</span>
                <span class="value"><?php echo htmlspecialchars(ucfirst((string) $discount['discount_type'])); ?></span>
            </div>
            <div class="info-row">
                <span class="label">Value Type:</span>
                <span class="value"><?php echo htmlspecialchars(ucfirst((string) $discount['value_type'])); ?></span>
            </div>
            <div class="info-row">
                <span class="label">Value:</span>
                <span class="value">
                    <?php echo strtolower((string) $discount['value_type']) === 'fixed'
                        ? htmlspecialchars(number_format((float) $discount['value']) . ' MMK')
                        : htmlspecialchars(rtrim(rtrim(number_format((float) $discount['value'], 2, '.', ''), '0'), '.') . ' %'); ?>
                </span>
            </div>
            <div class="info-row date-row">
                <span class="label">Start Date:</span>
                <span class="value"><?php echo !empty($discount['start_date']) ? htmlspecialchars(date('d/m/Y', strtotime((string) $discount['start_date']))) : '-'; ?></span>
            </div>
            <div class="info-row date-row">
                <span class="label">End Date:</span>
                <span class="value"><?php echo !empty($discount['end_date']) ? htmlspecialchars(date('d/m/Y', strtotime((string) $discount['end_date']))) : '-'; ?></span>
            </div>
        </section>

        <section class="apply-targets">
            <h2>Apply To:</h2>
            <form method="post">
                <input type="hidden" name="discount_id" value="<?php echo $discountId; ?>">
                <div class="apply-layout">
                    <aside class="apply-sidebar">
                        <button class="apply-tab active" type="button" data-target="apply-product">
                            <span>Product</span><span class="count"><?php echo count($targets['products']); ?></span>
                        </button>
                        <button class="apply-tab" type="button" data-target="apply-category">
                            <span>Category</span><span class="count"><?php echo count($targets['categories']); ?></span>
                        </button>
                        <button class="apply-tab" type="button" data-target="apply-subcategory">
                            <span>Sub-Category</span><span class="count"><?php echo count($targets['sub_categories']); ?></span>
                        </button>
                        <button class="apply-tab" type="button" data-target="apply-brand">
                            <span>Brand</span><span class="count"><?php echo count($targets['brands']); ?></span>
                        </button>
                    </aside>

                    <div class="apply-content">
                        <div class="apply-pane active" id="apply-product">
                            <div class="apply-search">
                                <div class="search-field">
                                    <input type="text" placeholder="Product Name" data-product-search>
                                    <i class="fa-solid fa-magnifying-glass"></i>
                                </div>
                                <button type="button" class="btn-search">Search</button>
                            </div>
                            <label class="select-all">
                                <input type="checkbox" data-select-all="apply-product">
                                <span>Select All</span>
                            </label>
                            <div class="apply-grid product-grid">
                                <?php if ($targets['products'] === []): ?>
                                    <div class="apply-empty">No products available.</div>
                                <?php else: ?>
                                    <?php foreach ($targets['products'] as $product): ?>
                                        <?php $selected = in_array((int) $product['product_id'], $conditions['product'], true); ?>
                                        <label
                                            class="apply-card<?php echo $selected ? ' selected' : ''; ?>"
                                            data-search="<?php echo htmlspecialchars(strtolower(trim((string) $product['name'] . ' ' . $product['brand_name']))); ?>"
                                        >
                                            <input type="checkbox" name="product_ids[]" value="<?php echo (int) $product['product_id']; ?>" <?php echo $selected ? 'checked' : ''; ?>>
                                            <div class="card-image">
                                                <img src="<?php echo htmlspecialchars((string) $product['image_url']); ?>" alt="<?php echo htmlspecialchars((string) $product['name']); ?>">
                                            </div>
                                            <div class="card-info">
                                                <p class="name"><?php echo htmlspecialchars((string) $product['name']); ?></p>
                                                <p class="brand"><?php echo htmlspecialchars((string) $product['brand_name']); ?></p>
                                                <p class="price"><?php echo htmlspecialchars(number_format((float) $product['price']) . ' MMK'); ?></p>
                                                <p class="stock">Stock: <span><?php echo (int) $product['stock_quantity']; ?></span></p>
                                            </div>
                                        </label>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="apply-pane" id="apply-category">
                            <label class="select-all">
                                <input type="checkbox" data-select-all="apply-category">
                                <span>Select All</span>
                            </label>
                            <div class="apply-grid tile-grid">
                                <?php if ($targets['categories'] === []): ?>
                                    <div class="apply-empty">No categories available.</div>
                                <?php else: ?>
                                    <?php foreach ($targets['categories'] as $category): ?>
                                        <?php $selected = in_array((int) $category['category_id'], $conditions['category'], true); ?>
                                        <label class="apply-card<?php echo $selected ? ' selected' : ''; ?>">
                                            <input type="checkbox" name="category_ids[]" value="<?php echo (int) $category['category_id']; ?>" <?php echo $selected ? 'checked' : ''; ?>>
                                            <div class="tile">
                                                <div class="tile-icon">
                                                    <?php if (!empty($category['image_url'])): ?>
                                                        <img src="<?php echo htmlspecialchars((string) $category['image_url']); ?>" alt="<?php echo htmlspecialchars((string) $category['name']); ?>">
                                                    <?php endif; ?>
                                                </div>
                                                <span><?php echo htmlspecialchars((string) $category['name']); ?></span>
                                            </div>
                                        </label>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="apply-pane" id="apply-subcategory">
                            <label class="select-all">
                                <input type="checkbox" data-select-all="apply-subcategory">
                                <span>Select All</span>
                            </label>
                            <?php if ($targets['sub_categories'] === []): ?>
                                <div class="apply-empty">No sub-categories available.</div>
                            <?php endif; ?>
                            <?php foreach ($targets['categories'] as $category): ?>
                                <?php
                                $subItems = $categorySubMap[(int) $category['category_id']] ?? [];
                                if ($subItems === []) {
                                    continue;
                                }
                                $allChecked = count(array_filter(
                                    $subItems,
                                    static fn (array $item): bool => in_array((int) $item['sub_category_id'], $conditions['sub_category'], true)
                                )) === count($subItems);
                                ?>
                                <div class="group-row open">
                                    <div class="group-header">
                                        <label class="group-check">
                                            <input type="checkbox" <?php echo $allChecked ? 'checked' : ''; ?>>
                                            <span><?php echo htmlspecialchars((string) $category['name']); ?></span>
                                        </label>
                                        <button type="button" class="group-toggle" aria-label="Toggle <?php echo htmlspecialchars((string) $category['name']); ?>">
                                            <i class="fa-solid fa-chevron-down"></i>
                                        </button>
                                    </div>
                                    <div class="apply-grid tile-grid">
                                        <?php foreach ($subItems as $subCategory): ?>
                                            <?php $selected = in_array((int) $subCategory['sub_category_id'], $conditions['sub_category'], true); ?>
                                            <label class="apply-card<?php echo $selected ? ' selected' : ''; ?>">
                                                <input type="checkbox" name="sub_category_ids[]" value="<?php echo (int) $subCategory['sub_category_id']; ?>" <?php echo $selected ? 'checked' : ''; ?>>
                                                <div class="tile">
                                                    <div class="tile-icon"></div>
                                                    <span><?php echo htmlspecialchars((string) $subCategory['name']); ?></span>
                                                </div>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="apply-pane" id="apply-brand">
                            <label class="select-all">
                                <input type="checkbox" data-select-all="apply-brand">
                                <span>Select All</span>
                            </label>
                            <div class="apply-grid tile-grid">
                                <?php if ($targets['brands'] === []): ?>
                                    <div class="apply-empty">No brands available.</div>
                                <?php else: ?>
                                    <?php foreach ($targets['brands'] as $brand): ?>
                                        <?php $selected = in_array((int) $brand['brand_id'], $conditions['brand'], true); ?>
                                        <label class="apply-card<?php echo $selected ? ' selected' : ''; ?>">
                                            <input type="checkbox" name="brand_ids[]" value="<?php echo (int) $brand['brand_id']; ?>" <?php echo $selected ? 'checked' : ''; ?>>
                                            <div class="tile">
                                                <div class="tile-icon">
                                                    <?php if (!empty($brand['image_url'])): ?>
                                                        <img src="<?php echo htmlspecialchars((string) $brand['image_url']); ?>" alt="<?php echo htmlspecialchars((string) $brand['name']); ?>">
                                                    <?php endif; ?>
                                                </div>
                                                <span><?php echo htmlspecialchars((string) $brand['name']); ?></span>
                                            </div>
                                        </label>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="apply-footer">
                    <span>Unsaved data will be deleted</span>
                    <div class="apply-actions">
                        <button type="submit" class="btn-save">Save</button>
                        <button type="button" class="btn-discard">Discard</button>
                    </div>
                </div>
            </form>
        </section>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        window.adminApplyDiscountsFlash = <?php echo json_encode($flash, JSON_UNESCAPED_SLASHES); ?>;
    </script>
    <script src="<?php echo htmlspecialchars(app_path('/admin/assets/js/apply-discounts.js')); ?>"></script>
    <script src="<?php echo htmlspecialchars(app_path('/admin/assets/js/admin.js')); ?>"></script>
</body>
</html>

