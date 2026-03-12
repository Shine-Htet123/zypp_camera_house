<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../catalog.php';

function admin_discount_generate_public_id(PDO $pdo): string
{
    $prefix = 'DCT';
    $datePart = date('Ymd');
    $like = $prefix . $datePart . '%';

    $statement = $pdo->prepare(
        'SELECT public_discount_id
         FROM discounts
         WHERE public_discount_id LIKE :prefix
         ORDER BY public_discount_id DESC
         LIMIT 1'
    );
    $statement->execute([':prefix' => $like]);
    $latestId = (string) $statement->fetchColumn();

    $nextNumber = 1;
    if ($latestId !== '' && preg_match('/(\d{4})$/', $latestId, $matches)) {
        $nextNumber = ((int) $matches[1]) + 1;
    }

    return $prefix . $datePart . str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
}

function admin_fetch_discount_valid_user_options(): array
{
    $pdo = get_database_connection();
    $options = [
        ['key' => 'standard', 'label' => 'Standard Customer'],
    ];
    $seenLabels = [
        strtolower('Standard Customer') => true,
    ];

    $statement = $pdo->query('SELECT id, tier_name FROM membership_tiers ORDER BY id ASC');
    foreach ($statement->fetchAll() as $tier) {
        $label = trim((string) $tier['tier_name']);
        if ($label === '') {
            continue;
        }

        $normalizedLabel = strtolower($label);
        if (isset($seenLabels[$normalizedLabel])) {
            continue;
        }

        $options[] = [
            'key' => 'tier:' . (int) $tier['id'],
            'label' => $label,
        ];
        $seenLabels[$normalizedLabel] = true;
    }

    return $options;
}

function admin_discount_parse_valid_users(?string $value): array
{
    $value = trim((string) $value);
    if ($value === '') {
        return [];
    }

    return array_values(array_filter(array_map('trim', explode(',', $value)), static fn (string $item): bool => $item !== ''));
}

function admin_discount_format_valid_user_labels(array $keys, array $options): array
{
    $labelMap = [];
    foreach ($options as $option) {
        $labelMap[$option['key']] = $option['label'];
    }

    $labels = [];
    foreach ($keys as $key) {
        if (isset($labelMap[$key])) {
            $labels[] = $labelMap[$key];
        }
    }

    return $labels;
}

function admin_fetch_discounts(array $filters = []): array
{
    $pdo = get_database_connection();
    $validUserOptions = admin_fetch_discount_valid_user_options();
    $sql = 'SELECT
            id,
            public_discount_id,
            name,
            discount_type,
            value_type,
            value,
            valid_user,
            priority,
            start_date,
            end_date,
            status
         FROM discounts
         WHERE 1 = 1';

    $bindings = [];
    $searchQuery = trim((string) ($filters['q'] ?? ''));
    if ($searchQuery !== '') {
        $searchBinding = '%' . $searchQuery . '%';
        $sql .= ' AND (
            public_discount_id LIKE :query_id
            OR name LIKE :query_name
        )';
        $bindings[':query_id'] = $searchBinding;
        $bindings[':query_name'] = $searchBinding;
    }

    $sql .= ' ORDER BY id ASC';

    $statement = $pdo->prepare($sql);
    $statement->execute($bindings);

    $discounts = $statement->fetchAll();
    foreach ($discounts as &$discount) {
        $validKeys = admin_discount_parse_valid_users($discount['valid_user']);
        $discount['valid_user_keys'] = $validKeys;
        $discount['users'] = admin_discount_format_valid_user_labels($validKeys, $validUserOptions);
        $discount['display_value'] = strtolower((string) $discount['value_type']) === 'fixed'
            ? number_format((float) $discount['value']) . ' MMK'
            : rtrim(rtrim(number_format((float) $discount['value'], 2, '.', ''), '0'), '.');
        $discount['start_display'] = !empty($discount['start_date']) ? date('d/m/Y', strtotime((string) $discount['start_date'])) : '';
        $discount['end_display'] = !empty($discount['end_date']) ? date('d/m/Y', strtotime((string) $discount['end_date'])) : '';
        $discount['start_input'] = !empty($discount['start_date']) ? date('Y-m-d', strtotime((string) $discount['start_date'])) : '';
        $discount['end_input'] = !empty($discount['end_date']) ? date('Y-m-d', strtotime((string) $discount['end_date'])) : '';
        $discount['discount_type_label'] = ucfirst((string) $discount['discount_type']);
        $discount['value_type_label'] = ucfirst((string) $discount['value_type']);
        $discount['status_label'] = ucfirst((string) $discount['status']);
    }

    return $discounts;
}

function admin_discount_validate(array $input): array
{
    $title = trim((string) ($input['title'] ?? ''));
    $discountType = strtolower(trim((string) ($input['discount_type'] ?? '')));
    $valueType = strtolower(trim((string) ($input['value_type'] ?? '')));
    $valueRaw = trim((string) ($input['value'] ?? ''));
    $startDate = trim((string) ($input['start_date'] ?? ''));
    $endDate = trim((string) ($input['end_date'] ?? ''));
    $status = strtolower(trim((string) ($input['status'] ?? '')));
    $validUsers = array_values(array_unique(array_filter((array) ($input['valid_users'] ?? []), static fn ($item): bool => trim((string) $item) !== '')));

    if ($title === '') {
        throw new InvalidArgumentException('Discount title is required.');
    }

    if (!in_array($discountType, ['membership', 'promotion', 'bundle'], true)) {
        throw new InvalidArgumentException('Discount type is invalid.');
    }

    if (!in_array($valueType, ['percentage', 'fixed'], true)) {
        throw new InvalidArgumentException('Value type is invalid.');
    }

    if ($valueRaw === '' || !is_numeric($valueRaw) || (float) $valueRaw < 0) {
        throw new InvalidArgumentException('Discount value must be a valid non-negative number.');
    }

    if ($valueType === 'percentage' && (float) $valueRaw > 100) {
        throw new InvalidArgumentException('Percentage discounts cannot exceed 100%.');
    }

    if ($startDate !== '' && strtotime($startDate) === false) {
        throw new InvalidArgumentException('Start date is invalid.');
    }

    if ($endDate !== '' && strtotime($endDate) === false) {
        throw new InvalidArgumentException('End date is invalid.');
    }

    if ($startDate !== '' && $endDate !== '' && strtotime($endDate) < strtotime($startDate)) {
        throw new InvalidArgumentException('End date must be after or equal to start date.');
    }

    if (!in_array($status, ['active', 'inactive'], true)) {
        throw new InvalidArgumentException('Status is invalid.');
    }

    return [
        'title' => $title,
        'discount_type' => $discountType,
        'value_type' => $valueType,
        'value' => (float) $valueRaw,
        'valid_user' => implode(',', $validUsers),
        'start_date' => $startDate !== '' ? date('Y-m-d 00:00:00', strtotime($startDate)) : null,
        'end_date' => $endDate !== '' ? date('Y-m-d 23:59:59', strtotime($endDate)) : null,
        'status' => $status,
        'priority' => max(1, (int) ($input['priority'] ?? 1)),
    ];
}

function admin_discount_save(array $input): int
{
    $pdo = get_database_connection();
    $discountId = (int) ($input['discount_id'] ?? 0);
    $validated = admin_discount_validate($input);

    if ($discountId > 0) {
        $statement = $pdo->prepare(
            'UPDATE discounts
             SET name = :name,
                 discount_type = :discount_type,
                 value_type = :value_type,
                 value = :value,
                 valid_user = :valid_user,
                 priority = :priority,
                 start_date = :start_date,
                 end_date = :end_date,
                 status = :status
             WHERE id = :id'
        );
        $statement->execute([
            ':name' => $validated['title'],
            ':discount_type' => $validated['discount_type'],
            ':value_type' => $validated['value_type'],
            ':value' => $validated['value'],
            ':valid_user' => $validated['valid_user'],
            ':priority' => $validated['priority'],
            ':start_date' => $validated['start_date'],
            ':end_date' => $validated['end_date'],
            ':status' => $validated['status'],
            ':id' => $discountId,
        ]);

        return $discountId;
    }

    $statement = $pdo->prepare(
        'INSERT INTO discounts
         (public_discount_id, name, discount_type, value_type, value, valid_user, priority, start_date, end_date, status)
         VALUES
         (:public_discount_id, :name, :discount_type, :value_type, :value, :valid_user, :priority, :start_date, :end_date, :status)'
    );
    $statement->execute([
        ':public_discount_id' => admin_discount_generate_public_id($pdo),
        ':name' => $validated['title'],
        ':discount_type' => $validated['discount_type'],
        ':value_type' => $validated['value_type'],
        ':value' => $validated['value'],
        ':valid_user' => $validated['valid_user'],
        ':priority' => $validated['priority'],
        ':start_date' => $validated['start_date'],
        ':end_date' => $validated['end_date'],
        ':status' => $validated['status'],
    ]);

    return (int) $pdo->lastInsertId();
}

function admin_fetch_discount_row(int $discountId): ?array
{
    $pdo = get_database_connection();
    $statement = $pdo->prepare('SELECT * FROM discounts WHERE id = :id LIMIT 1');
    $statement->execute([':id' => $discountId]);
    $discount = $statement->fetch();
    return $discount ?: null;
}

function admin_fetch_discount_conditions_grouped(int $discountId): array
{
    $pdo = get_database_connection();
    $statement = $pdo->prepare(
        'SELECT apply_to, ref_id
         FROM discount_conditions
         WHERE discount_id = :discount_id
         ORDER BY id ASC'
    );
    $statement->execute([':discount_id' => $discountId]);

    $grouped = [
        'product' => [],
        'category' => [],
        'sub_category' => [],
        'brand' => [],
    ];

    foreach ($statement->fetchAll() as $row) {
        $applyTo = (string) $row['apply_to'];
        if (isset($grouped[$applyTo])) {
            $grouped[$applyTo][] = (int) $row['ref_id'];
        }
    }

    return $grouped;
}

function admin_discount_save_conditions(int $discountId, array $input): void
{
    $pdo = get_database_connection();
    $pdo->beginTransaction();
    try {
        $pdo->prepare('DELETE FROM discount_conditions WHERE discount_id = :discount_id')
            ->execute([':discount_id' => $discountId]);

        $insert = $pdo->prepare(
            'INSERT INTO discount_conditions (discount_id, apply_to, ref_id)
             VALUES (:discount_id, :apply_to, :ref_id)'
        );

        $map = [
            'product' => array_map('intval', (array) ($input['product_ids'] ?? [])),
            'category' => array_map('intval', (array) ($input['category_ids'] ?? [])),
            'sub_category' => array_map('intval', (array) ($input['sub_category_ids'] ?? [])),
            'brand' => array_map('intval', (array) ($input['brand_ids'] ?? [])),
        ];

        foreach ($map as $applyTo => $ids) {
            foreach (array_values(array_unique(array_filter($ids, static fn (int $id): bool => $id > 0))) as $id) {
                $insert->execute([
                    ':discount_id' => $discountId,
                    ':apply_to' => $applyTo,
                    ':ref_id' => $id,
                ]);
            }
        }

        $pdo->commit();
    } catch (Throwable $exception) {
        $pdo->rollBack();
        throw $exception;
    }
}

function admin_fetch_discount_apply_targets(): array
{
    $categories = catalog_fetch_category_options();
    foreach ($categories as &$category) {
        $category['image_url'] = catalog_public_file_url($category['category_img'] ?? '', '');
    }

    $brands = catalog_fetch_brand_options();
    foreach ($brands as &$brand) {
        $brand['image_url'] = catalog_public_file_url($brand['logo_file'] ?? '', '');
    }

    return [
        'products' => catalog_fetch_admin_products(),
        'categories' => $categories,
        'sub_categories' => catalog_fetch_sub_category_options(),
        'brands' => $brands,
    ];
}

function admin_discount_supported_types(): array
{
    return ['membership', 'promotion', 'bundle'];
}

function admin_fetch_discount_priority_types(): array
{
    $pdo = get_database_connection();
    $supportedTypes = admin_discount_supported_types();
    $fallbackOrder = array_flip($supportedTypes);

    $statement = $pdo->query(
        'SELECT LOWER(discount_type) AS discount_type, MIN(priority) AS priority_rank
         FROM discounts
         WHERE LOWER(discount_type) IN ("membership", "promotion", "bundle")
         GROUP BY LOWER(discount_type)'
    );

    $priorityMap = [];
    foreach ($statement->fetchAll() as $row) {
        $type = (string) $row['discount_type'];
        $priorityMap[$type] = isset($row['priority_rank']) ? (int) $row['priority_rank'] : PHP_INT_MAX;
    }

    usort($supportedTypes, static function (string $left, string $right) use ($priorityMap, $fallbackOrder): int {
        $leftRank = $priorityMap[$left] ?? PHP_INT_MAX;
        $rightRank = $priorityMap[$right] ?? PHP_INT_MAX;
        if ($leftRank === $rightRank) {
            return ($fallbackOrder[$left] ?? 0) <=> ($fallbackOrder[$right] ?? 0);
        }

        return $leftRank <=> $rightRank;
    });

    return array_map(
        static fn (string $type): array => [
            'key' => $type,
            'label' => ucfirst($type),
        ],
        $supportedTypes
    );
}

function admin_fetch_discount_stack_rules(): array
{
    $pdo = get_database_connection();
    $supportedTypes = admin_discount_supported_types();
    admin_ensure_discount_stack_rules($pdo, $supportedTypes);

    $statement = $pdo->query(
        'SELECT id, LOWER(discount_type_a) AS discount_type_a, LOWER(discount_type_b) AS discount_type_b, allowed
         FROM discount_stack_rules
         WHERE LOWER(discount_type_a) IN ("membership", "promotion", "bundle")
           AND LOWER(discount_type_b) IN ("membership", "promotion", "bundle")
         ORDER BY id ASC'
    );

    $existing = [];
    foreach ($statement->fetchAll() as $row) {
        $existing[$row['discount_type_a'] . '|' . $row['discount_type_b']] = [
            'id' => (int) $row['id'],
            'allowed' => (bool) $row['allowed'],
        ];
    }

    $rules = [];
    $displayIndex = 1;
    $typeCount = count($supportedTypes);
    for ($i = 0; $i < $typeCount; $i++) {
        for ($j = $i + 1; $j < $typeCount; $j++) {
            $typeA = $supportedTypes[$i];
            $typeB = $supportedTypes[$j];

            $key = $typeA . '|' . $typeB;
            $stored = $existing[$key] ?? null;
            $rules[] = [
                'id' => $stored['id'] ?? 0,
                'no' => $displayIndex++,
                'type_a' => ucfirst($typeA),
                'type_b' => ucfirst($typeB),
                'type_a_key' => $typeA,
                'type_b_key' => $typeB,
                'allowed' => !empty($stored['allowed']),
            ];
        }
    }

    return $rules;
}

function admin_ensure_discount_stack_rules(PDO $pdo, array $supportedTypes): void
{
    $existingStatement = $pdo->query(
        'SELECT LOWER(discount_type_a) AS discount_type_a, LOWER(discount_type_b) AS discount_type_b
         FROM discount_stack_rules'
    );

    $existingKeys = [];
    foreach ($existingStatement->fetchAll() as $row) {
        $existingKeys[$row['discount_type_a'] . '|' . $row['discount_type_b']] = true;
    }

    $insertStatement = $pdo->prepare(
        'INSERT INTO discount_stack_rules (discount_type_a, discount_type_b, allowed)
         VALUES (:type_a, :type_b, :allowed)'
    );

    $typeCount = count($supportedTypes);
    for ($i = 0; $i < $typeCount; $i++) {
        for ($j = $i + 1; $j < $typeCount; $j++) {
            $typeA = $supportedTypes[$i];
            $typeB = $supportedTypes[$j];

            $key = $typeA . '|' . $typeB;
            if (isset($existingKeys[$key])) {
                continue;
            }

            $insertStatement->execute([
                ':type_a' => $typeA,
                ':type_b' => $typeB,
                ':allowed' => 0,
            ]);
        }
    }
}

function admin_save_discount_priority_types(array $orderedTypes): void
{
    $pdo = get_database_connection();
    $supportedMap = array_fill_keys(admin_discount_supported_types(), true);

    $normalized = [];
    foreach ($orderedTypes as $type) {
        $type = strtolower(trim((string) $type));
        if ($type === '' || !isset($supportedMap[$type]) || in_array($type, $normalized, true)) {
            continue;
        }
        $normalized[] = $type;
    }

    if (count($normalized) !== count($supportedMap)) {
        throw new InvalidArgumentException('Discount priority order is invalid.');
    }

    $statement = $pdo->prepare('UPDATE discounts SET priority = :priority WHERE LOWER(discount_type) = :discount_type');
    foreach ($normalized as $index => $type) {
        $statement->execute([
            ':priority' => $index + 1,
            ':discount_type' => $type,
        ]);
    }
}

function admin_save_discount_stack_rules(array $rules): void
{
    $pdo = get_database_connection();
    $supportedMap = array_fill_keys(admin_discount_supported_types(), true);

    $selectStatement = $pdo->prepare(
        'SELECT id
         FROM discount_stack_rules
         WHERE LOWER(discount_type_a) = :type_a
           AND LOWER(discount_type_b) = :type_b
         ORDER BY id ASC
         LIMIT 1'
    );
    $insertStatement = $pdo->prepare(
        'INSERT INTO discount_stack_rules (discount_type_a, discount_type_b, allowed)
         VALUES (:type_a, :type_b, :allowed)'
    );
    $updateStatement = $pdo->prepare(
        'UPDATE discount_stack_rules
         SET allowed = :allowed
         WHERE id = :id'
    );

    foreach ($rules as $rule) {
        $typeA = strtolower(trim((string) ($rule['type_a'] ?? '')));
        $typeB = strtolower(trim((string) ($rule['type_b'] ?? '')));
        if ($typeA === '' || $typeB === '' || $typeA === $typeB) {
            continue;
        }
        if (!isset($supportedMap[$typeA], $supportedMap[$typeB])) {
            continue;
        }

        $allowed = !empty($rule['allowed']) ? 1 : 0;
        $ruleId = (int) ($rule['id'] ?? 0);

        if ($ruleId <= 0) {
            $selectStatement->execute([
                ':type_a' => $typeA,
                ':type_b' => $typeB,
            ]);
            $ruleId = (int) $selectStatement->fetchColumn();
        }

        if ($ruleId > 0) {
            $updateStatement->execute([
                ':allowed' => $allowed,
                ':id' => $ruleId,
            ]);
            continue;
        }

        $insertStatement->execute([
            ':type_a' => $typeA,
            ':type_b' => $typeB,
            ':allowed' => $allowed,
        ]);
    }
}

