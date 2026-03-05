<?php
$proofs = [
    [
        'no' => 1,
        'payment_id' => 'PAY202601260001',
        'order_id' => '#202601260001',
        'customer_id' => 'ZCU202601260001',
        'amount' => '30,000,000 MMK',
        'status' => 'Unpaid',
        'method' => 'KBZPay',
        'proof' => '/storage/uploads/contents/logo.png',
        'order_date' => '20/12/2025',
    ],
    [
        'no' => 2,
        'payment_id' => 'PAY202601260001',
        'order_id' => '#202601260001',
        'customer_id' => 'ZCU202601260001',
        'amount' => '30,000,000 MMK',
        'status' => 'Paid',
        'method' => 'KBZPay',
        'proof' => '/storage/uploads/contents/logo.png',
        'order_date' => '20/12/2025',
    ],
    [
        'no' => 3,
        'payment_id' => 'PAY202601260001',
        'order_id' => '#202601260001',
        'customer_id' => 'ZCU202601260001',
        'amount' => '30,000,000 MMK',
        'status' => 'Pending',
        'method' => 'KBZPay',
        'proof' => '/storage/uploads/contents/logo.png',
        'order_date' => '20/12/2025',
    ],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include __DIR__ . '/head.php'; ?>
    <link rel="stylesheet" href="/admin/assets/css/payment-proof-uploads.css">
</head>
<body class="admin-page">
    <?php include __DIR__ . '/navbar.php'; ?>

    <main class="admin-content proof-page">
        <header class="proof-header">
            <h1>Payment Proof Uploads</h1>
            <div class="proof-actions">
                <div class="proof-search">
                    <div class="search-field">
                        <input type="text" placeholder="Customer ID/Order ID/Payment ID">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </div>
                    <button type="button" class="btn-search">Search</button>
                    <div class="filter-wrapper">
                        <button type="button" class="btn-filter" aria-label="Filter">
                            <i class="fa-solid fa-filter"></i>
                        </button>
                        <div class="filter-dropdown" id="proofFilterDropdown" aria-hidden="true">
                            <div class="filter-row">
                                <label for="filterProofStatus">Status</label>
                                <select id="filterProofStatus">
                                    <option value="">All</option>
                                    <option value="Paid">Paid</option>
                                    <option value="Unpaid">Unpaid</option>
                                    <option value="Pending">Pending</option>
                                </select>
                            </div>
                            <div class="filter-row">
                                <label for="filterProofMethod">Method</label>
                                <select id="filterProofMethod">
                                    <option value="">All</option>
                                    <option value="KBZPay">KBZPay</option>
                                    <option value="KBZ Bank">KBZ Bank</option>
                                    <option value="AYA Pay">AYA Pay</option>
                                    <option value="COD">COD</option>
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
                    <button type="button" class="btn-approve">Approve</button>
                    <button type="button" class="btn-reject">Reject</button>
                    <button type="button" class="btn-request">Request Re-Upload</button>
                </div>
            </div>
        </header>

        <section class="proof-table">
            <div class="proof-scroll">
                <div class="proof-head">
                    <span class="head-select">
                        <input type="checkbox" id="selectAllProofs">
                        <label for="selectAllProofs">All</label>
                    </span>
                    <span>No.</span>
                    <span>Payment<br>ID</span>
                    <span>Order<br>ID</span>
                    <span>Customer<br>ID</span>
                    <span>Amount</span>
                    <span>Status</span>
                    <span>Method</span>
                    <span>Proof</span>
                </div>
                <div class="proof-body">
                    <?php foreach ($proofs as $proof): ?>
                        <div
                            class="proof-row"
                            data-order-no="<?php echo htmlspecialchars($proof['order_id']); ?>"
                            data-order-date="<?php echo htmlspecialchars($proof['order_date']); ?>"
                            data-payment-status="<?php echo htmlspecialchars($proof['status']); ?>"
                            data-order-status="Pending"
                        >
                            <span><input type="checkbox" class="proof-check"></span>
                            <span><?php echo htmlspecialchars($proof['no']); ?></span>
                            <span class="payment-id-cell"><?php echo htmlspecialchars($proof['payment_id']); ?></span>
                            <span class="order-link order-id-link"><?php echo htmlspecialchars($proof['order_id']); ?></span>
                            <span><?php echo htmlspecialchars($proof['customer_id']); ?></span>
                            <span><?php echo htmlspecialchars($proof['amount']); ?></span>
                            <span class="status <?php echo strtolower($proof['status']); ?>">
                                <?php echo htmlspecialchars($proof['status']); ?>
                            </span>
                            <span><?php echo htmlspecialchars($proof['method']); ?></span>
                            <button type="button" class="proof-thumb" data-proof-src="<?php echo htmlspecialchars($proof['proof']); ?>">
                                <img src="<?php echo htmlspecialchars($proof['proof']); ?>" alt="Payment proof thumbnail">
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    </main>

    <div class="modal-overlay" id="proofOrderModal" aria-hidden="true">
        <div class="modal-card order-modal" role="dialog" aria-modal="true">
            <button type="button" class="modal-close" aria-label="Close">
                <i class="fa-solid fa-xmark"></i>
            </button>
            <h2 class="modal-title">
                Order No. <span id="modalOrderNo">#202601260001</span>
            </h2>
            <p class="modal-subtitle">
                Order Date - <span id="modalOrderDate">20/12/2025</span>
            </p>
            <h3 class="modal-section-title">Order Summary</h3>

            <div class="summary-table">
                <div class="summary-head">
                    <span>Qty.</span>
                    <span>Product Name</span>
                    <span>Price</span>
                </div>
                <div class="summary-row">
                    <span>1</span>
                    <span>Canon EOS R6 Mark II</span>
                    <span>3,000,000 MMK</span>
                </div>
                <div class="summary-row">
                    <span>2</span>
                    <span>Canon EOS R6 Mark II</span>
                    <span>3,000,000 MMK</span>
                </div>
                <div class="summary-row summary-total">
                    <span>Subtotal</span>
                    <span>6,000,000 MMK</span>
                </div>
                <div class="summary-row summary-total">
                    <span>Shipping Fees</span>
                    <span>5,000 MMK</span>
                </div>
                <div class="summary-row summary-total">
                    <span>Total</span>
                    <span>6,005,000 MMK</span>
                </div>
            </div>

            <div class="shipping-info">
                <h4>Shipping Info</h4>
                <p>Kyaw Ko Ko</p>
                <p>kyawko@gmail.com</p>
                <p>09771751530</p>
                <p>No. 96, Pyay Road, Hlaing Township, Yangon</p>
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
                <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore</p>
            </div>

            <button type="button" class="btn-download">Download E-receipt</button>
        </div>
    </div>

    <div class="modal-overlay" id="proofImageModal" aria-hidden="true">
        <div class="modal-card proof-modal" role="dialog" aria-modal="true">
            <button type="button" class="modal-close" aria-label="Close">
                <i class="fa-solid fa-xmark"></i>
            </button>
            <h2 class="modal-title">Payment Proof</h2>
            <p class="modal-subtitle">
                Payment ID - <span id="modalPaymentId">PAY202601260001</span>
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

    <script src="/admin/assets/js/payment-proof-uploads.js"></script>
    <script src="/admin/assets/js/admin.js"></script>
</body>
</html>
