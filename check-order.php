<!DOCTYPE html>
<html lang="en">
<head>
    <?php include('./head.php'); ?>
    <link rel="stylesheet" href="./assets/css/check-order.css">
</head>
<body>
    <?php include('./navbar.php') ?>

    <main class="check-order-container">
        <section class="order-card">
            <div class="order-meta">
                <h3>Order No. #202601260001</h3>
                <p>Order Date - 20/12/2025</p>
                <p>Order Time - 12:00:00</p>
            </div>

            <h4 class="order-summary-title">Order Summary</h4>
            <div class="summary-table">
                <div class="summary-row head">
                    <span class="cell">No.</span>
                    <span class="cell">Product Name</span>
                    <span class="cell">Qty.</span>
                    <span class="cell">Price</span>
                </div>
                <div class="summary-row">
                    <span class="cell">1</span>
                    <span class="cell">Canon EOS R6 Mark II</span>
                    <span class="cell">1</span>
                    <span class="cell">3,000,000 MMK</span>
                </div>
                <div class="summary-row">
                    <span class="cell">2</span>
                    <span class="cell">Canon EOS R6 Mark II</span>
                    <span class="cell">1</span>
                    <span class="cell">3,000,000 MMK</span>
                </div>
                <div class="summary-total">
                    <span class="cell label">Subtotal</span>
                    <span class="cell value">6,000,000 MMK</span>
                </div>
                <div class="summary-total">
                    <span class="cell label">Discount</span>
                    <span class="cell value discount">-60,000 MMK</span>
                </div>
                <div class="summary-total">
                    <span class="cell label">Grand Total</span>
                    <span class="cell value">5,940,000 MMK</span>
                </div>
                <div class="free-note">Congratulations! You got Free of Charges for delivery!</div>
            </div>

            <div class="order-details">
                <div class="detail-block">
                    <h5>Delivery Information</h5>
                    <p>Kyaw Ko Ko</p>
                    <p>kyawkokko@gmail.com</p>
                    <p>09771751530</p>
                    <p>No. 96, Pyay Road, Hlaing Township, Yangon</p>
                </div>
                <div class="detail-block">
                    <p><strong>Order Status</strong> <span class="status pending">Pending</span></p>
                    <p><strong>Payment Status</strong> <span class="status paid">Paid</span></p>
                    <p><strong>Additional Note</strong></p>
                    <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore.</p>
                </div>
            </div>

            <div class="order-actions">
                <a href="user-profile.php#track-order">Go Back to Profile</a>
                <button type="button" class="download-btn">Download E-receipt</button>
            </div>
        </section>
    </main>
    <?php include('./footer.php') ?>
</body>
</html>
