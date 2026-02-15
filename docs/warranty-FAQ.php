<!DOCTYPE html>
<html lang="en">
<head>
    <?php include('head.php') ?>
    <link rel="stylesheet" href="./assets/css/warranty-FAQ.css">
</head>
<body>
    <?php include('navbar.php') ?>

    <?php
        $warrantyData = [
            "cameras" => [
                "label" => "Cameras",
                "policy" => [
                    "Camera warranty covers manufacturing defects for 12 months.",
                    "Warranty is valid with original receipt and warranty card.",
                    "Physical damage or water damage is not covered.",
                    "Free inspection is available within the first 7 days.",
                    "Repairs are subject to parts availability."
                ],
                "faqs" => [
                    ["q" => "Question 1", "a" => "Camera warranty covers manufacturing defects only."],
                    ["q" => "Question 2", "a" => "Bring the receipt and warranty card for service."],
                    ["q" => "Question 3", "a" => "Accidental damage is excluded from coverage."],
                    ["q" => "Question 4", "a" => "Inspection is free within 7 days of purchase."],
                    ["q" => "Question 5", "a" => "Repair time depends on parts availability."]
                ]
            ],
            "lenses" => [
                "label" => "Lenses",
                "policy" => [
                    "Lens warranty covers optical and mechanical defects for 12 months.",
                    "Warranty excludes scratches on glass or physical damage.",
                    "Calibration is covered within the first 30 days.",
                    "Service requires proof of purchase.",
                    "Third-party modifications void warranty."
                ],
                "faqs" => [
                    ["q" => "Question 1", "a" => "Optical defects are covered under warranty."],
                    ["q" => "Question 2", "a" => "Scratches are not covered."],
                    ["q" => "Question 3", "a" => "Calibration is free in the first 30 days."],
                    ["q" => "Question 4", "a" => "Proof of purchase is required."],
                    ["q" => "Question 5", "a" => "Modifications void warranty."]
                ]
            ],
            "tripods" => [
                "label" => "Tripods",
                "policy" => [
                    "Tripod warranty covers manufacturing defects for 6 months.",
                    "Warranty does not cover wear on rubber feet.",
                    "Locks and clamps are covered for defects.",
                    "Service requires original receipt.",
                    "Warranty excludes misuse or overload."
                ],
                "faqs" => [
                    ["q" => "Question 1", "a" => "Tripod warranty is 6 months."],
                    ["q" => "Question 2", "a" => "Rubber feet wear is not covered."],
                    ["q" => "Question 3", "a" => "Clamp defects are covered."],
                    ["q" => "Question 4", "a" => "Receipt required for service."],
                    ["q" => "Question 5", "a" => "Overload damage is excluded."]
                ]
            ],
            "lightings" => [
                "label" => "Lightings",
                "policy" => [
                    "Lighting warranty covers defects for 12 months.",
                    "Bulbs are considered consumables and not covered.",
                    "Power adapter defects are covered.",
                    "Warranty requires proof of purchase.",
                    "Unauthorized repairs void warranty."
                ],
                "faqs" => [
                    ["q" => "Question 1", "a" => "Lighting warranty is 12 months."],
                    ["q" => "Question 2", "a" => "Bulbs are not covered."],
                    ["q" => "Question 3", "a" => "Power adapters are covered."],
                    ["q" => "Question 4", "a" => "Proof of purchase required."],
                    ["q" => "Question 5", "a" => "Unauthorized repairs void warranty."]
                ]
            ]
        ];
        $defaultCategory = "cameras";
    ?>

    <main class="warranty-page">
        <header class="warranty-hero">
            <h2>Warranty & FAQs</h2>
            <p>Please select the category you want to know.</p>
        </header>

        <section class="category-selector">
            <button class="arrow-btn" type="button" aria-label="Previous category">
                <i class="fa-solid fa-chevron-left"></i>
            </button>
            <div class="category-list">
                <button class="category-card active" type="button" data-category="cameras">
                    <div class="category-image">
                        <img src="../storage/uploads/categories/cameras-category.png" alt="Cameras">
                    </div>
                    <span>Cameras</span>
                </button>
                <button class="category-card" type="button" data-category="lenses">
                    <div class="category-image">
                        <img src="../storage/uploads/categories/lenses-category.png" alt="Lenses">
                    </div>
                    <span>Lenses</span>
                </button>
                <button class="category-card" type="button" data-category="tripods">
                    <div class="category-image">
                        <img src="../storage/uploads/categories/tripods-category.png" alt="Tripods">
                    </div>
                    <span>Tripods</span>
                </button>
                <button class="category-card" type="button" data-category="lightings">
                    <div class="category-image">
                        <img src="../storage/uploads/categories/lightings-category.png" alt="Lightings">
                    </div>
                    <span>Lightings</span>
                </button>
                <?php foreach ($warrantyData as $key => $item): ?>
                    <button class="category-card <?php echo $key === $defaultCategory ? 'active' : ''; ?>" type="button" data-category="<?php echo $key; ?>">
                        <div class="category-image">
                            <img src="../storage/uploads/categories/<?php echo $key; ?>-category.png" alt="<?php echo htmlspecialchars($item['label']); ?>">
                        </div>
                        <span><?php echo htmlspecialchars($item['label']); ?></span>
                    </button>
                <?php endforeach; ?>
            </div>
            <button class="arrow-btn" type="button" aria-label="Next category">
                <i class="fa-solid fa-chevron-right"></i>
            </button>
        </section>

        <section class="warranty-policy">
            <h3>Warranty Policy</h3>
            <?php foreach ($warrantyData as $key => $item): ?>
                <ol class="policy-list policy-group <?php echo $key === $defaultCategory ? 'active' : ''; ?>" data-category="<?php echo $key; ?>">
                    <?php foreach ($item['policy'] as $policy): ?>
                        <li><?php echo htmlspecialchars($policy); ?></li>
                    <?php endforeach; ?>
                </ol>
            <?php endforeach; ?>
        </section>

        <section class="faq-section">
            <h3>FAQs</h3>
            <?php foreach ($warrantyData as $key => $item): ?>
                <div class="faq-list faq-group <?php echo $key === $defaultCategory ? 'active' : ''; ?>" data-category="<?php echo $key; ?>">
                    <?php foreach ($item['faqs'] as $index => $faq): ?>
                        <div class="faq-item <?php echo $index === 0 ? 'open' : ''; ?>">
                            <button type="button" class="faq-question">
                                <span><?php echo htmlspecialchars($faq['q']); ?></span>
                                <i class="fa-solid fa-chevron-down"></i>
                            </button>
                            <div class="faq-answer">
                                <p><?php echo htmlspecialchars($faq['a']); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </section>
    </main>

    <?php include('./footer.php') ?>

    <script src="./assets/js/warranty-FAQ.js"></script>
</body>
</html>
