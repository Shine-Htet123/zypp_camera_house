<?php
require_once __DIR__ . '/../config/admin_bootstrap.php';
require_once __DIR__ . '/../database/admin/orders.php';

$searchQuery = trim((string) ($_GET['q'] ?? ''));
$proofs = admin_fetch_payment_proofs();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include __DIR__ . '/head.php'; ?>
    <link rel="stylesheet" href="<?php echo htmlspecialchars(app_path('/admin/assets/css/payment-proof-uploads.css')); ?>">
</head>
<body class="admin-page">
    <?php include __DIR__ . '/navbar.php'; ?>

    <main class="admin-content proof-page">
        <header class="proof-header">
            <h1>Payment Proof Uploads</h1>
            <div class="proof-actions">
                <div class="proof-search">
                    <form class="proof-search-form admin-search-form" method="get" action="<?php echo htmlspecialchars(app_path('/admin/payment-proof-uploads.php')); ?>">
                        <div class="admin-search-box">
                            <input type="text" id="proofSearchInput" name="q" value="<?php echo htmlspecialchars($searchQuery); ?>" placeholder="Customer ID/Order ID/Payment ID">
                            <button type="submit" class="search-icon" aria-label="Search">
                                <i class="fa-solid fa-magnifying-glass"></i>
                            </button>
                        </div>
                        <button type="submit" class="admin-search-submit" id="proofSearchButton">Search</button>
                        <a href="<?php echo htmlspecialchars(app_path('/admin/payment-proof-uploads.php')); ?>" class="admin-show-all">Show All</a>
                    </form>
                    <div class="filter-wrapper">
                        <button type="button" class="btn-filter" aria-label="Filter">
                            <i class="fa-solid fa-filter"></i>
                        </button>
                        <div class="filter-dropdown" id="proofFilterDropdown" aria-hidden="true">
                            <div class="filter-row">
                                <label for="filterProofStatus">Status</label>
                                <select id="filterProofStatus">
                                    <option value="">All</option>
                                    <?php foreach (admin_orders_payment_status_options() as $status): ?>
                                        <option value="<?php echo htmlspecialchars($status); ?>"><?php echo htmlspecialchars($status); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="filter-row">
                                <label for="filterProofMethod">Method</label>
                                <select id="filterProofMethod">
                                    <option value="">All</option>
                                    <?php
                                    $methods = array_values(array_unique(array_map(
                                        static fn (array $proof): string => (string) $proof['payment_method'],
                                        $proofs
                                    )));
                                    sort($methods);
                                    foreach ($methods as $method):
                                    ?>
                                        <option value="<?php echo htmlspecialchars($method); ?>"><?php echo htmlspecialchars($method); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="filter-actions">
                                <button type="button" class="btn-filter-apply">Apply</button>
                                <button type="button" class="btn-filter-clear" id="filterProofReset">Clear</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="proof-bulk-actions">
                    <button type="button" class="btn-approve" disabled>Approve</button>
                    <button type="button" class="btn-reject" disabled>Reject</button>
                    <button type="button" class="btn-request" disabled>Request Re-Upload</button>
                </div>
            </div>
        </header>

        <section class="proof-table">
            <div class="proof-scroll table-scroll">
                <table class="admin-table proof-table-grid">
                    <thead>
                        <tr class="proof-head">
                            <th class="head-select">
                                <input type="checkbox" id="selectAllProofs">
                                <label for="selectAllProofs">All</label>
                            </th>
                            <th>No.</th>
                            <th>Payment<br>ID</th>
                            <th>Order<br>ID</th>
                            <th>Customer<br>ID</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Method</th>
                            <th>Proof</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="proof-empty-row"<?php echo $proofs === [] ? '' : ' style="display:none"'; ?>>
                            <td colspan="9">No payment proofs found.</td>
                        </tr>
                        <?php foreach ($proofs as $proof): ?>
                            <tr
                                class="proof-row"
                                data-order-id="<?php echo (int) $proof['order_id']; ?>"
                                data-order-no="<?php echo htmlspecialchars((string) $proof['order_no_display']); ?>"
                                data-order-date="<?php echo htmlspecialchars((string) $proof['order_date_display']); ?>"
                                data-payment-status="<?php echo htmlspecialchars((string) $proof['status_label']); ?>"
                                data-order-status="<?php echo htmlspecialchars((string) $proof['order_status_label']); ?>"
                                data-payment-method="<?php echo htmlspecialchars((string) $proof['payment_method']); ?>"
                                data-payment-id="<?php echo (int) $proof['payment_id']; ?>"
                            >
                                <td><input type="checkbox" class="proof-check" value="<?php echo (int) $proof['payment_id']; ?>"></td>
                                <td><?php echo (int) $proof['no']; ?></td>
                                <td class="payment-id-cell"><?php echo htmlspecialchars((string) $proof['payment_id_display']); ?></td>
                                <td class="order-link order-id-link"><?php echo htmlspecialchars((string) $proof['order_no_display']); ?></td>
                                <td><?php echo htmlspecialchars((string) $proof['public_user_id']); ?></td>
                                <td><?php echo htmlspecialchars((string) $proof['amount_display']); ?></td>
                                <td>
                                    <span class="status <?php echo strtolower((string) $proof['status_label']); ?>">
                                        <?php echo htmlspecialchars((string) $proof['status_label']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars((string) $proof['payment_method']); ?></td>
                                <td>
                                    <button type="button" class="proof-thumb" data-proof-src="<?php echo htmlspecialchars((string) $proof['payment_proof_file']); ?>">
                                        <img src="<?php echo htmlspecialchars((string) $proof['payment_proof_file']); ?>" alt="Payment proof thumbnail">
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <div class="modal-overlay" id="proofOrderModal" aria-hidden="true">
        <div class="modal-card order-modal" role="dialog" aria-modal="true">
            <button type="button" class="modal-close" aria-label="Close">
                <i class="fa-solid fa-xmark"></i>
            </button>
            <h2 class="modal-title">
                Order No. <span id="modalOrderNo">#-</span>
            </h2>
            <p class="modal-subtitle">
                Order Date - <span id="modalOrderDate">-</span>
            </p>
            <h3 class="modal-section-title">Order Summary</h3>

            <div class="summary-table" id="proofOrderSummaryTable">
                <div class="summary-head">
                    <span>Qty.</span>
                    <span>Product Name</span>
                    <span>Price</span>
                </div>
            </div>

            <div class="shipping-info">
                <h4>Shipping Info</h4>
                <p id="modalShipName">-</p>
                <p id="modalShipEmail">-</p>
                <p id="modalShipPhone">-</p>
                <p id="modalShipAddress">-</p>
            </div>

            <div class="status-info">
                <p>
                    <strong>Order Status</strong>
                    <span class="status order pending" id="modalOrderStatus">Pending</span>
                </p>
                <p>
                    <strong>Payment Status</strong>
                    <span class="status payment paid" id="modalPaymentStatus">Paid</span>
                </p>
            </div>

            <div class="note-section">
                <h4>Additional Note</h4>
                <p id="modalAdditionalNote">-</p>
            </div>

            <button type="button" class="btn-download" id="modalReceiptButton">Download E-receipt</button>
        </div>
    </div>

    <div class="modal-overlay" id="proofImageModal" aria-hidden="true">
        <div class="modal-card proof-modal" role="dialog" aria-modal="true">
            <button type="button" class="modal-close" aria-label="Close">
                <i class="fa-solid fa-xmark"></i>
            </button>
            <h2 class="modal-title">Payment Proof</h2>
            <p class="modal-subtitle">
                Payment ID - <span id="modalPaymentId">-</span>
            </p>
            <div class="proof-preview">
                <img id="proofImage" src="" alt="Payment proof">
            </div>
            <div class="proof-actions-row">
                <a class="btn-icon" id="downloadProof" href="#" download aria-label="Download proof">
                    <i class="fa-solid fa-download"></i>
                </a>
                <button type="button" class="btn-icon btn-copy" id="copyProof" aria-label="Copy proof">
                    <i class="fa-regular fa-copy"></i>
                    <span class="copy-text" aria-hidden="true">Copied</span>
                </button>
            </div>
        </div>
    </div>

    <script src="<?php echo htmlspecialchars(app_path('/admin/assets/js/payment-proof-uploads.js')); ?>"></script>
    <script src="<?php echo htmlspecialchars(app_path('/admin/assets/js/admin.js')); ?>"></script>
</body>
</html>

