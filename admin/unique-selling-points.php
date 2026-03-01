<?php
$points = [
    [
        'id' => 1,
        'title' => 'Lorem ipsum dolor sit amet, consectetur',
        'description' => 'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.',
    ],
    [
        'id' => 2,
        'title' => 'Lorem ipsum dolor sit amet, consectetur',
        'description' => 'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.',
    ],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include __DIR__ . '/head.php'; ?>
    <link rel="stylesheet" href="/admin/assets/css/unique-selling-points.css">
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
            <div class="usp-head">
                <span>No.</span>
                <span>Icon</span>
                <span>Title</span>
                <span>Description</span>
                <span>Action</span>
            </div>
            <div class="usp-body">
                <?php foreach ($points as $point): ?>
                    <div class="usp-row" data-id="<?php echo htmlspecialchars($point['id']); ?>">
                        <span><?php echo htmlspecialchars($point['id']); ?></span>
                        <span class="icon-cell">
                            <span class="icon-placeholder"></span>
                        </span>
                        <span class="usp-title"><?php echo htmlspecialchars($point['title']); ?></span>
                        <span class="usp-desc"><?php echo htmlspecialchars($point['description']); ?></span>
                        <span class="usp-actions">
                            <button type="button" class="icon-btn edit" aria-label="Edit point">
                                <i class="fa-regular fa-pen-to-square"></i>
                            </button>
                            <button type="button" class="icon-btn delete" aria-label="Delete point">
                                <i class="fa-regular fa-trash-can"></i>
                            </button>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <div class="modal-overlay" id="uspModal" aria-hidden="true">
            <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="uspModalTitle">
                <button type="button" class="modal-close" aria-label="Close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
                <h2 id="uspModalTitle">Add</h2>
                <form class="modal-form">
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
                    <input type="file" id="uspIconInput" accept="image/*" hidden>

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
                            <button type="button" class="btn-footer save">Save</button>
                            <button type="button" class="btn-footer discard">Discard</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </main>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="/admin/assets/js/unique-selling-points.js"></script>
<script src="/admin/assets/js/admin.js"></script>
</body>
</html>
