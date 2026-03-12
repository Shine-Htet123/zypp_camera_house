<?php

require_once __DIR__ . '/../../config/database.php';

function wholesale_submit_survey(array $input): array
{
    $companyName = trim((string) ($input['business_name'] ?? ''));
    $contactPerson = trim((string) ($input['contact_person'] ?? ''));
    $phone = trim((string) ($input['phone_number'] ?? ''));
    $email = trim((string) ($input['email_address'] ?? ''));
    $businessType = trim((string) ($input['business_type'] ?? ''));
    $additionalNote = trim((string) ($input['additional_note'] ?? ''));
    $agreement = !empty($input['agreement']);

    if ($companyName === '') {
        throw new InvalidArgumentException('Business name is required.');
    }

    if ($contactPerson === '') {
        throw new InvalidArgumentException('Contact person name is required.');
    }

    if ($phone === '') {
        throw new InvalidArgumentException('Phone number is required.');
    }

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new InvalidArgumentException('A valid email address is required.');
    }

    if ($businessType === '') {
        throw new InvalidArgumentException('Business type is required.');
    }

    if (!$agreement) {
        throw new InvalidArgumentException('You must confirm the information provided is correct.');
    }

    $pdo = get_database_connection();
    $statement = $pdo->prepare(
        'INSERT INTO wholesale_survey (
            company_name,
            contact_person,
            phone,
            email,
            business_type,
            additional_note
        ) VALUES (
            :company_name,
            :contact_person,
            :phone,
            :email,
            :business_type,
            :additional_note
        )'
    );

    $statement->execute([
        ':company_name' => $companyName,
        ':contact_person' => $contactPerson,
        ':phone' => $phone,
        ':email' => $email,
        ':business_type' => $businessType,
        ':additional_note' => $additionalNote !== '' ? $additionalNote : null,
    ]);

    return [
        'answer_id' => (int) $pdo->lastInsertId(),
        'company_name' => $companyName,
        'contact_person' => $contactPerson,
        'phone' => $phone,
        'email' => $email,
        'business_type' => $businessType,
        'additional_note' => $additionalNote,
    ];
}
