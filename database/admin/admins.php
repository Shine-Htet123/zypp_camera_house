<?php

require_once __DIR__ . '/../../app/services/admin_auth.php';

function admin_list_admin_cards(array $filters = []): array
{
    return admin_auth_list_admins($filters);
}

function admin_invite_admin(array $input): array
{
    return admin_auth_create_invite_link($input);
}

function admin_remove_admin(int $adminId): void
{
    admin_auth_delete_admin_account($adminId);
}

