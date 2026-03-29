<?php
require_once __DIR__ . '/../config/admin_bootstrap.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../database/admin/unique_selling_points.php';

$setFlash = static function (string $message, string $type = 'success'): void {
    $_SESSION['admin_usp_flash'] = [
        'message' => $message,
        'type' => $type,
    ];
};

$consumeFlash = static function (): ?array {
    $flash = $_SESSION['admin_usp_flash'] ?? null;
    unset($_SESSION['admin_usp_flash']);
    return is_array($flash) ? $flash : null;
};

$isAjaxRequest = strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';
$respondJson = static function (array $payload, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($payload, JSON_UNESCAPED_SLASHES);
    exit;
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action = (string) ($_POST['usp_action'] ?? '');
        $message = '';
        if ($action === 'save') {
            admin_usp_save($_POST, $_FILES);
            $message = 'Unique selling point saved successfully.';
        } elseif ($action === 'delete') {
            admin_usp_delete((int) ($_POST['entity_id'] ?? 0));
            $message = 'Unique selling point deleted successfully.';
        }

        if ($isAjaxRequest) {
            $respondJson([
                'success' => true,
                'message' => $message,
                'points' => admin_fetch_unique_selling_points(),
            ]);
        }

        $setFlash($message);
        header('Location: ' . app_path('/admin/unique-selling-points.php'));
        exit;
    } catch (Throwable $exception) {
        if ($isAjaxRequest) {
            $respondJson([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }

        $setFlash($exception->getMessage(), 'error');
        header('Location: ' . app_path('/admin/unique-selling-points.php'));
        exit;
    }
}

$flash = $consumeFlash();
$points = admin_fetch_unique_selling_points();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include __DIR__ . '/head.php'; ?>
    <link rel="stylesheet" href="<?php echo htmlspecialchars(app_path('/admin/assets/css/unique-selling-points.css')); ?>">
</head>
<body class="admin-page">
    <?php include __DIR__ . '/navbar.php'; ?>

    <main class="admin-content usp-page">
        <header class="usp-header">
            <h1>Unique Selling Points</h1>
            <button type="button" class="btn-new" data-modal="usp">
                <i class="fa-solid fa-plus"></i>
                New
            </button>
        </header>

        <section class="usp-table">
            <div class="table-scroll usp-scroll">
                <table class="admin-table usp-table-grid">
                    <thead>
                        <tr class="usp-head">
                            <th>No.</th>
                            <th>Icon</th>
                            <th>Title</th>
                            <th>Description</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($points as $point): ?>
                            <tr
                                class="usp-row"
                                data-id="<?php echo (int) $point['item_id']; ?>"
                                data-title="<?php echo htmlspecialchars((string) $point['title']); ?>"
                                data-description="<?php echo htmlspecialchars((string) $point['subtitle']); ?>"
                                data-icon="<?php echo htmlspecialchars((string) $point['icon_url']); ?>"
                            >
                                <td><?php echo (int) $point['item_id']; ?></td>
                                <td class="icon-cell">
                                    <?php if (!empty($point['icon_url'])): ?>
                                        <img class="usp-icon-image" src="<?php echo htmlspecialchars((string) $point['icon_url']); ?>" alt="<?php echo htmlspecialchars((string) $point['title']); ?>">
                                    <?php else: ?>
                                        <span class="icon-placeholder"></span>
                                    <?php endif; ?>
                                </td>
                                <td class="usp-title"><?php echo htmlspecialchars((string) $point['title']); ?></td>
                                <td class="usp-desc"><?php echo htmlspecialchars((string) $point['subtitle']); ?></td>
                                <td class="usp-actions">
                                    <button type="button" class="icon-btn edit" aria-label="Edit point">
                                        <i class="fa-regular fa-pen-to-square"></i>
                                    </button>
                                    <button type="button" class="icon-btn delete" aria-label="Delete point">
                                        <i class="fa-regular fa-trash-can"></i>
                                    </button>
                                </td>
                                <td hidden>
                                    <form method="post" class="usp-delete-form">
                                        <input type="hidden" name="usp_action" value="delete">
                                        <input type="hidden" name="entity_id" value="<?php echo (int) $point['item_id']; ?>">
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <div class="modal-overlay" id="uspModal" aria-hidden="true">
            <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="uspModalTitle">
                <button type="button" class="modal-close" aria-label="Close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
                <h2 id="uspModalTitle">Add</h2>
                <form class="modal-form" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="usp_action" value="save">
                    <input type="hidden" name="entity_id" value="0">
                    <div class="usp-icon-row">
                        <span>Icon:</span>
                        <button type="button" class="btn-upload" data-upload="uspIconInput">
                            <i class="fa-solid fa-upload"></i>
                            Upload
                        </button>
                    </div>
                    <div class="modal-icon-preview" id="uspIconPreview">
                        <span class="icon-placeholder"></span>
                    </div>
                    <input type="file" id="uspIconInput" name="uspIcon" accept="image/*" hidden>

                    <label class="modal-field">
                        <span>Title:</span>
                        <input type="text" name="uspTitle">
                    </label>
                    <label class="modal-field textarea">
                        <span>Description:</span>
                        <textarea name="uspDescription" rows="4"></textarea>
                    </label>
                    <div class="modal-footer">
                        <span class="modal-note">Unsaved data will be deleted</span>
                        <div class="modal-actions">
                            <button type="submit" class="btn-footer save">Save</button>
                            <button type="button" class="btn-footer discard">Discard</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        window.adminUspFlash = <?php echo json_encode($flash, JSON_UNESCAPED_SLASHES); ?>;
    </script>
    <script src="<?php echo htmlspecialchars(app_path('/admin/assets/js/unique-selling-points.js')); ?>"></script>
    <script src="<?php echo htmlspecialchars(app_path('/admin/assets/js/admin.js')); ?>"></script>
</body>
</html>

