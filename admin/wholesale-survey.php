<?php
$answers = [
    [
        'business_name' => 'Example Co. Ltd.',
        'contact_person' => 'Steven Chou',
        'phone' => '09123456789',
        'email' => 'example@email.com',
        'business_type' => 'Expertise',
        'note' => 'This is the additional notes from the survey.....',
    ],
    [
        'business_name' => 'Example Co. Ltd.',
        'contact_person' => 'Steven Chou',
        'phone' => '09123456789',
        'email' => 'example@email.com',
        'business_type' => 'Expertise',
        'note' => 'This is the additional notes from the survey.....',
    ],
    [
        'business_name' => 'Example Co. Ltd.',
        'contact_person' => 'Steven Chou',
        'phone' => '09123456789',
        'email' => 'example@email.com',
        'business_type' => 'Expertise',
        'note' => 'This is the additional notes from the survey.....',
    ],
    [
        'business_name' => 'Example Co. Ltd.',
        'contact_person' => 'Steven Chou',
        'phone' => '09123456789',
        'email' => 'example@email.com',
        'business_type' => 'Expertise',
        'note' => 'This is the additional notes from the survey.....',
    ],
    [
        'business_name' => 'Example Co. Ltd.',
        'contact_person' => 'Steven Chou',
        'phone' => '09123456789',
        'email' => 'example@email.com',
        'business_type' => 'Expertise',
        'note' => 'This is the additional notes from the survey.....',
    ],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include __DIR__ . '/head.php'; ?>
    <link rel="stylesheet" href="/admin/assets/css/wholesale-survey.css">
</head>
<body class="admin-page">
    <?php include __DIR__ . '/navbar.php'; ?>

    <main class="admin-content wholesale-page">
        <header class="wholesale-header">
            <h1>Wholesale Survey</h1>
        </header>

        <section class="wholesale-section">
            <h2>Business Types</h2>
            <div class="wholesale-card chart-card">
                <div id="companyTypesChart" class="chart-canvas" aria-label="Company types chart"></div>
            </div>
        </section>

        <section class="wholesale-section">
            <h2>User Answers</h2>
            <div class="wholesale-card answers-card">
                <div class="answers-scroll">
                    <div class="answers-head">
                        <span>Business Name</span>
                        <span>Contact Person</span>
                        <span>Phone No.</span>
                        <span>Email</span>
                        <span>Business Type</span>
                        <span>Note</span>
                    </div>
                    <div class="answers-body">
                        <?php foreach ($answers as $row): ?>
                            <div
                                class="answers-row"
                                data-business="<?php echo htmlspecialchars($row['business_name']); ?>"
                                data-contact="<?php echo htmlspecialchars($row['contact_person']); ?>"
                                data-phone="<?php echo htmlspecialchars($row['phone']); ?>"
                                data-email="<?php echo htmlspecialchars($row['email']); ?>"
                                data-type="<?php echo htmlspecialchars($row['business_type']); ?>"
                                data-note="<?php echo htmlspecialchars($row['note']); ?>"
                            >
                                <span><?php echo htmlspecialchars($row['business_name']); ?></span>
                                <span><?php echo htmlspecialchars($row['contact_person']); ?></span>
                                <span><?php echo htmlspecialchars($row['phone']); ?></span>
                                <span><?php echo htmlspecialchars($row['email']); ?></span>
                                <span><?php echo htmlspecialchars($row['business_type']); ?></span>
                                <span class="note"><?php echo htmlspecialchars($row['note']); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </section>
    </main>

    </div>
</div>

<div class="modal-overlay" id="surveyModal" aria-hidden="true">
    <div class="modal-card survey-modal" role="dialog" aria-modal="true">
        <button type="button" class="modal-close" aria-label="Close">
            <i class="fa-solid fa-xmark"></i>
        </button>
        <h2 class="modal-title">Survey Details</h2>
        <div class="survey-details">
            <div class="detail-row">
                <span>Business Name:</span>
                <p id="detailBusiness"></p>
            </div>
            <div class="detail-row">
                <span>Contact Person:</span>
                <p id="detailContact"></p>
            </div>
            <div class="detail-row">
                <span>Phone No.:</span>
                <p id="detailPhone"></p>
            </div>
            <div class="detail-row">
                <span>Email:</span>
                <p id="detailEmail"></p>
            </div>
            <div class="detail-row">
                <span>Business Type:</span>
                <p id="detailType"></p>
            </div>
            <div class="detail-row note">
                <span>Note:</span>
                <p id="detailNote"></p>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script src="/admin/assets/js/wholesale-survey.js"></script>
<script src="/admin/assets/js/admin.js"></script>
</body>
</html>
