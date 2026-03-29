<?php
require_once __DIR__ . '/app/services/checkout.php';

customer_auth_require_login('/receipt.php');
$order = checkout_fetch_latest_confirmation();
if (!$order) {
    checkout_flash_set('No order receipt found yet.', 'error');
    customer_auth_redirect('/user-profile.php#orders');
}

$orderStatus = ucfirst((string) ($order['status'] ?? 'pending'));
$paymentStatus = ucfirst((string) (($order['payment_row_status'] ?? $order['payment_status']) ?? 'pending'));
$orderDate = strtotime((string) ($order['created_at'] ?? 'now'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include __DIR__ . '/head.php'; ?>
    <style id="receipt-export-style">
        *,
        *::before,
        *::after {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 28px;
            background: #f3f3f3;
            color: #241a14;
            font-family: Arial, sans-serif;
        }

        .receipt-shell {
            max-width: 860px;
            margin: 0 auto;
        }

        .receipt-toolbar {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            margin-bottom: 18px;
        }

        .receipt-download-btn {
            border: none;
            border-radius: 999px;
            height: 46px;
            padding: 0 22px;
            background: #5d4e47;
            color: #ffffff;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
        }

        .receipt-download-btn:disabled {
            opacity: 0.72;
            cursor: wait;
        }

        .receipt-download-feedback {
            margin: 0 0 18px;
            font-size: 13px;
            color: #5a4a40;
            text-align: right;
        }

        .receipt-card {
            background: #ffffff;
            border: 1px solid #ddd5cf;
            border-radius: 18px;
            padding: 32px;
            box-shadow: 0 18px 34px rgba(59, 38, 21, 0.08);
        }

        .receipt-header {
            display: flex;
            justify-content: space-between;
            gap: 24px;
            padding-bottom: 20px;
            border-bottom: 1px solid #e4ddd7;
            margin-bottom: 24px;
        }

        .receipt-brand h1 {
            margin: 0 0 8px;
            font-size: 28px;
            line-height: 1.1;
        }

        .receipt-brand p,
        .receipt-meta p,
        .receipt-block p {
            margin: 0;
            line-height: 1.5;
            font-size: 14px;
        }

        .receipt-meta {
            text-align: right;
        }

        .receipt-title {
            margin: 0 0 18px;
            font-size: 18px;
        }

        .receipt-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .receipt-table th,
        .receipt-table td {
            padding: 12px 10px;
            border-bottom: 1px solid #ece5de;
            text-align: left;
            font-size: 14px;
        }

        .receipt-table th:last-child,
        .receipt-table td:last-child {
            text-align: right;
        }

        .receipt-totals {
            margin-left: auto;
            max-width: 320px;
            display: grid;
            gap: 10px;
        }

        .receipt-total-row {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            font-size: 15px;
        }

        .receipt-total-row strong {
            font-size: 16px;
        }

        .receipt-total-row.discount strong {
            color: var(--color-danger);
        }

        .receipt-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
            margin-top: 28px;
        }

        .receipt-block {
            padding: 18px;
            border: 1px solid #ece5de;
            border-radius: 14px;
            background: #fcfbfa;
        }

        .receipt-block h2 {
            margin: 0 0 10px;
            font-size: 16px;
        }

        .receipt-status {
            display: inline-block;
            margin-top: 10px;
            padding: 6px 12px;
            border-radius: 999px;
            background: #f0ece8;
            font-size: 13px;
            font-weight: 600;
        }

        .receipt-note {
            margin-top: 18px;
            font-size: 13px;
            color: #5a4a40;
        }

        .receipt-preview-overlay {
            position: fixed;
            inset: 0;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 18px;
            background: rgba(22, 16, 12, 0.82);
            z-index: 1000;
        }

        .receipt-preview-overlay.is-open {
            display: flex;
        }

        .receipt-preview-card {
            width: min(100%, 760px);
            max-height: 100%;
            background: #ffffff;
            border-radius: 18px;
            padding: 18px;
            overflow: auto;
        }

        .receipt-preview-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 12px;
        }

        .receipt-preview-head h2 {
            margin: 0;
            font-size: 18px;
        }

        .receipt-preview-head p {
            margin: 4px 0 0;
            font-size: 13px;
            color: #5a4a40;
            line-height: 1.45;
        }

        .receipt-preview-close {
            border: none;
            background: transparent;
            color: #5d4e47;
            font-size: 28px;
            line-height: 1;
            cursor: pointer;
        }

        .receipt-preview-image {
            display: block;
            width: 100%;
            height: auto;
            border-radius: 12px;
            background: #ffffff;
        }

        @media (max-width: 720px) {
            body {
                padding: 14px;
            }

            .receipt-toolbar {
                justify-content: stretch;
                flex-direction: column;
                align-items: stretch;
            }

            .receipt-download-btn {
                width: 100%;
            }

            .receipt-card {
                padding: 20px;
            }

            .receipt-preview-card {
                padding: 14px;
            }

            .receipt-header,
            .receipt-grid {
                grid-template-columns: 1fr;
                display: grid;
            }

            .receipt-meta {
                text-align: left;
            }
        }

        @media print {
            body {
                background: #ffffff;
                padding: 0;
            }

            .receipt-toolbar {
                display: none;
            }

            .receipt-shell {
                max-width: none;
                margin: 0;
            }

            .receipt-card {
                border: none;
                border-radius: 0;
                box-shadow: none;
                padding: 0;
            }
        }
    </style>
</head>
<body>
    <main class="receipt-shell">
        <div class="receipt-toolbar">
            <button type="button" class="receipt-download-btn" data-download-button>Download E-Receipt</button>
        </div>
        <p class="receipt-download-feedback" data-download-feedback>Download your receipt as an image.</p>

        <section class="receipt-card" data-receipt-card data-order-id="<?php echo htmlspecialchars((string) $order['public_order_id']); ?>">
            <header class="receipt-header">
                <div class="receipt-brand">
                    <h1>ZYPP Camera House</h1>
                    <p>Official E-Receipt</p>
                </div>
                <div class="receipt-meta">
                    <p><strong>Order No.</strong> #<?php echo htmlspecialchars((string) $order['public_order_id']); ?></p>
                    <p><strong>Order Date</strong> <?php echo htmlspecialchars(date('d.m.Y', $orderDate)); ?></p>
                    <p><strong>Order Time</strong> <?php echo htmlspecialchars(date('H:i:s', $orderDate)); ?></p>
                </div>
            </header>

            <h2 class="receipt-title">Order Summary</h2>

            <table class="receipt-table">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Product Name</th>
                        <th>Qty.</th>
                        <th>Price</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (($order['items'] ?? []) as $index => $item): ?>
                        <tr>
                            <td><?php echo $index + 1; ?></td>
                            <td><?php echo htmlspecialchars((string) ($item['name'] ?? '')); ?></td>
                            <td><?php echo (int) ($item['qty'] ?? 0); ?></td>
                            <td><?php echo checkout_format_mmk((float) ($item['line_subtotal'] ?? $item['final_price'] ?? 0)); ?> MMK</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="receipt-totals">
                <div class="receipt-total-row">
                    <span>Subtotal</span>
                    <strong><?php echo checkout_format_mmk((float) ($order['subtotal'] ?? 0)); ?> MMK</strong>
                </div>
                <div class="receipt-total-row discount">
                    <span>Total Discount</span>
                    <strong>-<?php echo checkout_format_mmk((float) ($order['total_discount'] ?? 0)); ?> MMK</strong>
                </div>
                <div class="receipt-total-row">
                    <span>Grand Total</span>
                    <strong><?php echo checkout_format_mmk((float) ($order['grand_total'] ?? 0)); ?> MMK</strong>
                </div>
            </div>

            <div class="receipt-grid">
                <div class="receipt-block">
                    <h2>Delivery Information</h2>
                    <p><?php echo htmlspecialchars(trim(((string) ($order['first_name'] ?? '')) . ' ' . ((string) ($order['last_name'] ?? '')))); ?></p>
                    <p><?php echo htmlspecialchars((string) ($order['email'] ?? '')); ?></p>
                    <p><?php echo htmlspecialchars((string) ($order['phone'] ?? '')); ?></p>
                    <?php if (($order['delivery_type'] ?? 'delivery') === 'pickup'): ?>
                        <p>Pick up at store</p>
                    <?php else: ?>
                        <p>
                            <?php
                            echo htmlspecialchars(
                                implode(', ', array_filter([
                                    (string) ($order['address'] ?? ''),
                                    (string) ($order['township'] ?? ''),
                                    (string) ($order['city'] ?? ''),
                                ]))
                            );
                            ?>
                        </p>
                    <?php endif; ?>
                </div>

                <div class="receipt-block">
                    <h2>Order Details</h2>
                    <p><strong>Order Status:</strong> <?php echo htmlspecialchars($orderStatus); ?></p>
                    <p><strong>Payment Status:</strong> <?php echo htmlspecialchars($paymentStatus); ?></p>
                    <p><strong>Delivery Method:</strong> <?php echo htmlspecialchars((string) (($order['delivery_method_name'] ?? '') !== '' ? $order['delivery_method_name'] : '-')); ?></p>
                    <p><strong>Additional Note:</strong> <?php echo htmlspecialchars((string) (($order['additional_note'] ?? '') !== '' ? $order['additional_note'] : '-')); ?></p>
                    <span class="receipt-status">Official Customer Copy</span>
                </div>
            </div>

            <p class="receipt-note">This receipt is generated electronically by ZYPP Camera House.</p>
        </section>
    </main>

    <div class="receipt-preview-overlay" data-receipt-preview-overlay>
        <div class="receipt-preview-card">
            <div class="receipt-preview-head">
                <div>
                    <h2>Save Receipt Image</h2>
                    <p>On iPhone or iPad, press and hold the image, then choose “Save to Photos”.</p>
                </div>
                <button type="button" class="receipt-preview-close" aria-label="Close preview" data-receipt-preview-close>&times;</button>
            </div>
            <img src="" alt="Receipt preview" class="receipt-preview-image" data-receipt-preview-image>
        </div>
    </div>

    <script>
        window.addEventListener('load', () => {
            const receiptCard = document.querySelector('[data-receipt-card]');
            const styleTag = document.getElementById('receipt-export-style');
            const downloadButton = document.querySelector('[data-download-button]');
            const downloadFeedback = document.querySelector('[data-download-feedback]');
            const previewOverlay = document.querySelector('[data-receipt-preview-overlay]');
            const previewImage = document.querySelector('[data-receipt-preview-image]');
            const previewClose = document.querySelector('[data-receipt-preview-close]');

            if (!receiptCard || !styleTag || !downloadButton) {
                return;
            }

            const setFeedback = (message, isError = false) => {
                if (!downloadFeedback) {
                    return;
                }

                downloadFeedback.textContent = message;
                downloadFeedback.style.color = isError ? '#b31212' : '#5a4a40';
            };

            const isIosDevice = /iPad|iPhone|iPod/.test(window.navigator.userAgent)
                || (window.navigator.platform === 'MacIntel' && window.navigator.maxTouchPoints > 1);

            if (isIosDevice) {
                downloadButton.textContent = 'Save E-Receipt';
                setFeedback('Tap the button to open a saveable receipt image.');
            }

            const closePreview = () => {
                if (!previewOverlay || !previewImage) {
                    return;
                }

                previewOverlay.classList.remove('is-open');
                previewImage.src = '';
            };

            const openPreview = (src) => {
                if (!previewOverlay || !previewImage) {
                    return;
                }

                previewImage.src = src;
                previewOverlay.classList.add('is-open');
            };

            const downloadReceipt = () => {
                const width = Math.ceil(Math.max(receiptCard.offsetWidth, receiptCard.scrollWidth));
                const height = Math.ceil(Math.max(receiptCard.offsetHeight, receiptCard.scrollHeight));
                const scale = 2;
                const clone = receiptCard.cloneNode(true);
                clone.style.margin = '0';
                clone.style.width = `${width}px`;
                clone.style.minWidth = `${width}px`;
                clone.style.boxShadow = 'none';

                const serializer = new XMLSerializer();
                const markup = serializer.serializeToString(clone);
                const styleText = styleTag.textContent || '';
                const svgMarkup = `
                    <svg xmlns="http://www.w3.org/2000/svg" width="${width}" height="${height}" viewBox="0 0 ${width} ${height}">
                        <foreignObject width="100%" height="100%">
                            <div xmlns="http://www.w3.org/1999/xhtml" style="width:${width}px;height:${height}px;background:#ffffff;">
                                <style>${styleText}</style>
                                ${markup}
                            </div>
                        </foreignObject>
                    </svg>
                `;
                const url = `data:image/svg+xml;charset=utf-8,${encodeURIComponent(svgMarkup)}`;
                const image = new Image();

                image.onload = () => {
                    const canvas = document.createElement('canvas');
                    canvas.width = width * scale;
                    canvas.height = height * scale;
                    const context = canvas.getContext('2d');

                    if (!context) {
                        downloadButton.disabled = false;
                        setFeedback('Your browser could not prepare the receipt image.', true);
                        return;
                    }

                    context.scale(scale, scale);
                    context.fillStyle = '#ffffff';
                    context.fillRect(0, 0, width, height);
                    context.drawImage(image, 0, 0, width, height);

                    const mimeType = 'image/png';
                    const filename = 'zypp-receipt-<?php echo htmlspecialchars((string) $order['public_order_id']); ?>.png';

                    canvas.toBlob((fileBlob) => {
                        if (!fileBlob) {
                            downloadButton.disabled = false;
                            setFeedback('Download failed. Please try again.', true);
                            return;
                        }

                        const downloadUrl = URL.createObjectURL(fileBlob);

                        if (isIosDevice || !('download' in HTMLAnchorElement.prototype)) {
                            const dataUrl = canvas.toDataURL(mimeType);
                            openPreview(dataUrl);
                            downloadButton.disabled = false;
                            setFeedback('Receipt preview is ready. Press and hold the image to save it on iPhone or iPad.');
                            URL.revokeObjectURL(downloadUrl);
                            return;
                        }

                        const link = document.createElement('a');
                        link.href = downloadUrl;
                        link.download = filename;
                        document.body.appendChild(link);
                        link.click();
                        link.remove();
                        URL.revokeObjectURL(downloadUrl);
                        downloadButton.disabled = false;
                        setFeedback('Receipt downloaded as PNG.');
                    }, mimeType);
                };

                image.onerror = () => {
                    downloadButton.disabled = false;
                    setFeedback('Download failed on this browser. Please try again.', true);
                };

                image.src = url;
            };

            downloadButton.addEventListener('click', () => {
                downloadButton.disabled = true;
                setFeedback(isIosDevice ? 'Preparing your receipt preview...' : 'Preparing your receipt download...');
                downloadReceipt();
            });

            previewClose?.addEventListener('click', closePreview);
            previewOverlay?.addEventListener('click', (event) => {
                if (event.target === previewOverlay) {
                    closePreview();
                }
            });
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    closePreview();
                }
            });
        });
    </script>
</body>
</html>
