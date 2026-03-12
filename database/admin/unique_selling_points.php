<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/catalog_management.php';

function admin_usp_get_section_id(PDO $pdo): int
{
    catalog_ensure_unique_selling_point_tables();
    $uspSectionsTable = catalog_usp_sections_table();

    $statement = $pdo->prepare(
        'SELECT section_id
         FROM ' . $uspSectionsTable . '
         WHERE page = :page AND type = :type
         ORDER BY section_id ASC
         LIMIT 1'
    );
    $statement->execute([
        ':page' => 'home',
        ':type' => 'unique_selling_points',
    ]);

    $sectionId = (int) $statement->fetchColumn();
    if ($sectionId > 0) {
        return $sectionId;
    }

    $insert = $pdo->prepare(
        'INSERT INTO ' . $uspSectionsTable . ' (name, page, type, position, active)
         VALUES (:name, :page, :type, 0, 1)'
    );
    $insert->execute([
        ':name' => 'Unique Selling Points',
        ':page' => 'home',
        ':type' => 'unique_selling_points',
    ]);

    return (int) $pdo->lastInsertId();
}

function admin_fetch_unique_selling_points(): array
{
    $pdo = get_database_connection();
    catalog_ensure_unique_selling_point_tables();
    $uspSectionsTable = catalog_usp_sections_table();
    $uspItemsTable = catalog_usp_items_table();

    $statement = $pdo->prepare(
        'SELECT
            ci.item_id,
            ci.title,
            ci.subtitle,
            ci.link_url,
            ci.active
         FROM ' . $uspItemsTable . ' ci
         INNER JOIN ' . $uspSectionsTable . ' cs ON cs.section_id = ci.section_id
         WHERE cs.page = :page AND cs.type = :type
         ORDER BY ci.item_id ASC'
    );
    $statement->execute([
        ':page' => 'home',
        ':type' => 'unique_selling_points',
    ]);

    $items = $statement->fetchAll();
    foreach ($items as &$item) {
        $item['icon_url'] = catalog_public_file_url($item['link_url'], '');
    }

    return $items;
}

function admin_usp_save(array $input, array $files): void
{
    $pdo = get_database_connection();
    catalog_ensure_unique_selling_point_tables();
    $uspItemsTable = catalog_usp_items_table();
    $itemId = (int) ($input['entity_id'] ?? 0);
    $title = trim((string) ($input['uspTitle'] ?? ''));
    $description = trim((string) ($input['uspDescription'] ?? ''));

    if ($title === '') {
        throw new InvalidArgumentException('Title is required.');
    }

    if ($description === '') {
        throw new InvalidArgumentException('Description is required.');
    }

    $iconPath = admin_catalog_store_upload($files['uspIcon'] ?? null, 'usp');
    $sectionId = admin_usp_get_section_id($pdo);

    if ($itemId > 0) {
        $current = $pdo->prepare('SELECT link_url FROM ' . $uspItemsTable . ' WHERE item_id = :item_id');
        $current->execute([':item_id' => $itemId]);
        $existingPath = $current->fetchColumn();

        $update = $pdo->prepare(
            'UPDATE ' . $uspItemsTable . '
             SET title = :title,
                 subtitle = :subtitle,
                 link_url = :link_url,
                 active = 1
             WHERE item_id = :item_id'
        );
        $update->execute([
            ':title' => $title,
            ':subtitle' => $description,
            ':link_url' => $iconPath ?: $existingPath,
            ':item_id' => $itemId,
        ]);

        if ($iconPath && is_string($existingPath) && $existingPath !== '') {
            admin_catalog_delete_file($existingPath);
        }
        return;
    }

    $insert = $pdo->prepare(
        'INSERT INTO ' . $uspItemsTable . ' (section_id, title, subtitle, link_url, active)
         VALUES (:section_id, :title, :subtitle, :link_url, 1)'
    );
    $insert->execute([
        ':section_id' => $sectionId,
        ':title' => $title,
        ':subtitle' => $description,
        ':link_url' => $iconPath,
    ]);
}

function admin_usp_delete(int $itemId): void
{
    $pdo = get_database_connection();
    catalog_ensure_unique_selling_point_tables();
    $uspItemsTable = catalog_usp_items_table();

    $current = $pdo->prepare('SELECT link_url FROM ' . $uspItemsTable . ' WHERE item_id = :item_id');
    $current->execute([':item_id' => $itemId]);
    $existingPath = $current->fetchColumn();

    $delete = $pdo->prepare('DELETE FROM ' . $uspItemsTable . ' WHERE item_id = :item_id');
    $delete->execute([':item_id' => $itemId]);

    if (is_string($existingPath) && $existingPath !== '') {
        admin_catalog_delete_file($existingPath);
    }
}
