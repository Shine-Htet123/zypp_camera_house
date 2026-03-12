<?php

require_once __DIR__ . '/../../config/database.php';

function admin_fetch_wholesale_survey_answers(): array
{
    $pdo = get_database_connection();
    $statement = $pdo->query(
        'SELECT
            answer_id,
            company_name,
            contact_person,
            phone,
            email,
            business_type,
            additional_note
         FROM wholesale_survey
         ORDER BY answer_id DESC'
    );

    $rows = $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];

    return array_map(static function (array $row): array {
        return [
            'answer_id' => (int) $row['answer_id'],
            'business_name' => (string) ($row['company_name'] ?? ''),
            'contact_person' => (string) ($row['contact_person'] ?? ''),
            'phone' => (string) ($row['phone'] ?? ''),
            'email' => (string) ($row['email'] ?? ''),
            'business_type' => (string) ($row['business_type'] ?? ''),
            'note' => (string) ($row['additional_note'] ?? ''),
        ];
    }, $rows);
}

function admin_fetch_wholesale_survey_chart_data(): array
{
    $pdo = get_database_connection();
    $statement = $pdo->query(
        'SELECT business_type, COUNT(*) AS total
         FROM wholesale_survey
         GROUP BY business_type
         ORDER BY total DESC, business_type ASC'
    );

    $rows = $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $categories = [];
    $series = [];

    foreach ($rows as $row) {
        $categories[] = (string) ($row['business_type'] ?? '');
        $series[] = (int) ($row['total'] ?? 0);
    }

    return [
        'categories' => $categories,
        'series' => $series,
    ];
}
