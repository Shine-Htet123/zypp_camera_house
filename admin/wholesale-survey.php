<?php
require_once __DIR__ . '/../config/admin_bootstrap.php';
require_once __DIR__ . '/../database/admin/wholesale_survey.php';

$answers = admin_fetch_wholesale_survey_answers();
$chartData = admin_fetch_wholesale_survey_chart_data();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include __DIR__ . '/head.php'; ?>
    <link rel="stylesheet" href="<?php echo htmlspecialchars(app_path('/admin/assets/css/wholesale-survey.css')); ?>">
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
                <div class="answers-scroll table-scroll">
                    <table class="admin-table answers-table-grid">
                        <thead>
                            <tr class="answers-head">
                                <th>Business Name</th>
                                <th>Contact Person</th>
                                <th>Phone No.</th>
                                <th>Email</th>
                                <th>Business Type</th>
                                <th>Note</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($answers): ?>
                                <?php foreach ($answers as $row): ?>
                                    <tr
                                        class="answers-row"
                                        data-business="<?php echo htmlspecialchars($row['business_name']); ?>"
                                        data-contact="<?php echo htmlspecialchars($row['contact_person']); ?>"
                                        data-phone="<?php echo htmlspecialchars($row['phone']); ?>"
                                        data-email="<?php echo htmlspecialchars($row['email']); ?>"
                                        data-type="<?php echo htmlspecialchars($row['business_type']); ?>"
                                        data-note="<?php echo htmlspecialchars($row['note']); ?>"
                                    >
                                        <td><?php echo htmlspecialchars($row['business_name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['contact_person']); ?></td>
                                        <td><?php echo htmlspecialchars($row['phone']); ?></td>
                                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                                        <td><?php echo htmlspecialchars($row['business_type']); ?></td>
                                        <td class="note"><?php echo htmlspecialchars($row['note'] !== '' ? $row['note'] : '-'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr class="answers-empty-row">
                                    <td colspan="6">No wholesale survey responses found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
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

<script>
    window.wholesaleSurveyChartData = <?php echo json_encode($chartData, JSON_UNESCAPED_SLASHES); ?>;
</script>
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script src="<?php echo htmlspecialchars(app_path('/admin/assets/js/wholesale-survey.js')); ?>"></script>
    <script src="<?php echo htmlspecialchars(app_path('/admin/assets/js/admin.js')); ?>"></script>
</body>
</html>

