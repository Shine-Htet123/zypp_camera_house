<!DOCTYPE html>
<html lang="en">
<head>
    <?php include 'head.php'; ?>
    <link rel="stylesheet" href="./assets/css/user-profile.css">
</head>
<body>
    <?php include 'navbar.php'; ?>

    <main class="profile-page">
        <div class="profile-layout">
            <aside class="profile-sidebar">
                <nav class="profile-nav">
                    <a href="#profile" class="active">Profile</a>
                    <a href="#orders">My Orders</a>
                    <a href="#track-order">Track Order</a>
                    <a href="#membership">Membership</a>
                    <a href="#settings">Settings</a>
                </nav>
                <button class="logout-btn" type="button">Log Out</button>
            </aside>

            <section class="profile-content">
                <div class="profile-section" id="profile">
                    <div class="section-header">
                        <span>Profile</span>
                        <button class="edit-btn" type="button" id="profile-edit-btn">
                            <i class="fa-regular fa-pen-to-square"></i>
                        </button>
                    </div>

                    <div class="profile-view">
                        <div class="profile-list">
                            <div class="profile-row">
                                <span class="label">Full Name:</span>
                                <span class="value">Kyaw Ko Ko</span>
                            </div>
                            <div class="profile-row">
                                <span class="label">Phone:</span>
                                <span class="value">09123456789</span>
                            </div>
                            <div class="profile-row">
                                <span class="label">Email:</span>
                                <span class="value email">kyawkokko123@gmail.com</span>
                            </div>
                            <div class="profile-row">
                                <span class="label">Address:</span>
                                <span class="value">No. 96, Pyay Road</span>
                            </div>
                            <div class="profile-row double">
                                <div class="pair">
                                    <span class="label">Township:</span>
                                    <span class="value">Kamaryut</span>
                                </div>
                                <div class="pair">
                                    <span class="label">City:</span>
                                    <span class="value">Yangon</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <form class="profile-form" id="profile-form">
                        <div class="form-row two-col">
                            <div class="field">
                                <label>Full Name</label>
                                <input type="text" name="full_name" value="Kyaw Ko Ko">
                            </div>
                            <div class="field">
                                <label>Phone</label>
                                <input type="text" name="phone" value="09123456789">
                            </div>
                        </div>
                        <div class="form-row two-col">
                            <div class="field">
                                <label>Email</label>
                                <input type="email" name="email" value="kyawkokko123@gmail.com">
                            </div>
                            <div class="field">
                                <label>Address</label>
                                <input type="text" name="address" value="No. 96, Pyay Road">
                            </div>
                        </div>
                        <div class="form-row two-col">
                            <div class="field">
                                <label>Township</label>
                                <input type="text" name="township" value="Kamaryut">
                            </div>
                            <div class="field">
                                <label>City</label>
                                <input type="text" name="city" value="Yangon">
                            </div>
                        </div>
                        <div class="form-actions">
                            <button type="button" class="cancel-btn" id="profile-cancel-btn">Cancel</button>
                            <button type="button" class="save-btn">Save</button>
                        </div>
                    </form>
                </div>

                <div class="profile-section" id="orders">
                    <div class="section-header">My Orders</div>
                    <div class="orders-table">
                        <div class="orders-scroll">
                            <div class="orders-inner">
                                <div class="table-row table-head">
                                    <span>Order No.</span>
                                    <span>Date</span>
                                    <span>Status</span>
                                    <span>Payment</span>
                                    <span>Total</span>
                                </div>
                                <div class="orders-body">
                                    <div class="table-row">
                                        <span>#10123</span>
                                        <span>14 December 2025</span>
                                        <span class="status delivered">Delivered</span>
                                        <span>Paid</span>
                                        <span>3,000,000 MMK</span>
                                    </div>
                                    <div class="table-row">
                                        <span>#10124</span>
                                        <span>20 December 2025</span>
                                        <span class="status cancelled">Canceled</span>
                                        <span>Unpaid</span>
                                        <span>3,000,000 MMK</span>
                                    </div>
                                    <div class="table-row">
                                        <span>#10125</span>
                                        <span>24 December 2025</span>
                                        <span class="status pending">Pending</span>
                                        <span>Unpaid</span>
                                        <span>3,000,000 MMK</span>
                                    </div>
                                    <div class="table-row">
                                        <span>#10126</span>
                                        <span>30 December 2025</span>
                                        <span class="status delivered">Delivered</span>
                                        <span>Paid</span>
                                        <span>3,000,000 MMK</span>
                                    </div>
                                    <div class="table-row">
                                        <span>#10127</span>
                                        <span>02 January 2026</span>
                                        <span class="status pending">Pending</span>
                                        <span>Unpaid</span>
                                        <span>3,000,000 MMK</span>
                                    </div>
                                    <div class="table-row">
                                        <span>#10128</span>
                                        <span>05 January 2026</span>
                                        <span class="status cancelled">Canceled</span>
                                        <span>Unpaid</span>
                                        <span>3,000,000 MMK</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="profile-section" id="track-order">
                    <div class="section-header">Track Order</div>
                    <div class="track-card">
                        <form class="track-form" id="track-form">
                            <div class="field">
                                <label>Email</label>
                                <input type="email" name="track_email" placeholder="email@example.com">
                            </div>
                            <div class="field">
                                <label>Phone</label>
                                <input type="text" name="track_phone" placeholder="0900000">
                            </div>
                            <div class="field">
                                <label>Order No.</label>
                                <input type="text" name="track_order" placeholder="#10001">
                            </div>
                            <button type="submit" class="track-btn">Find My Order</button>
                        </form>
                    </div>

                    <div class="track-result hidden" id="track-result">
                        <div class="track-meta">
                            <h4>Order No. #202601260001</h4>
                            <p>Order Date - 20/12/2025</p>
                            <p>Order Time - 12:00:00</p>
                        </div>
                        <h5>Order Summary</h5>
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
                            <div class="free-note">Congratulations! You got <span>Free of Charges</span> for delivery!</div>
                        </div>

                        <div class="track-details">
                            <div class="detail-block">
                                <h3>Delivery Information</h3>
                                <p>Kyaw Ko Ko</p>
                                <p>kyawko@gmail.com</p>
                                <p>09/8/1230</p>
                                <p>No. 86, Pyay Road, Hlaing Township, Yangon</p>
                            </div>
                            <div class="detail-block">
                                <p><strong>Order Status</strong> <span class="status pending">Pending</span></p>
                                <p><strong>Payment Status</strong> <span class="status paid">Paid</span></p>
                                <p><strong>Additional Note</strong></p>
                                <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt.</p>
                            </div>
                        </div>
                        <div class="track-actions">
                            <a id="close-track-result">Close</a>
                            <button type="button" class="download-btn">Download E-receipt</button>
                        </div>
                    </div>
                </div>

                <div class="profile-section" id="membership">
                    <div class="section-header">Membership</div>
                    <div class="membership-card">
                        <div class="membership-icon">
                            <img src="./assets/images/logo.png" alt="Logo or Mascort">
                        </div>
                        <div class="membership-text">
                            <h4>ZYPP Pro Creator</h4>
                            <div class="membership-progress">
                                <div class="progress-bar">
                                    <span class="progress-fill" style="width: 38%;"></span>
                                </div>
                            </div>
                            <p class="required-amount">Upgrade to <strong class="next-level">ZYPP Master Creator</strong> by purchasing more items worth 5,000,000 MMK.</p>
                            <p class="membership-meta">You joined since <strong>01.01.2026</strong></p>
                            <p class="membership-meta">You’ve spent <strong>5,000,000 MMK</strong>.</p>
                            <button type="button" class="membership-btn">View ZYPP Benefits</button>
                        </div>
                    </div>
                </div>

                <div class="profile-section" id="settings">
                    <div class="section-header">Settings</div>
                    <div class="settings-list">
                        <div class="settings-row">
                            <span>Password:</span>
                            <span>**********</span>
                            <button type="button" class="icon-btn">
                                <i class="fa-regular fa-pen-to-square"></i>
                            </button>
                        </div>
                        <a href="#" class="settings-link">Forgot Password</a>
                        <a href="#" class="settings-link danger">Delete this Account</a>
                    </div>
                </div>

                <div class="mobile-logout">
                    <button class="logout-btn">Log Out</button>
                </div>
            </section>
        </div>
    </main>

    <script src="./assets/js/navbar.js"></script>
    <script src="./assets/js/user-profile.js"></script>
</body>
</html>
