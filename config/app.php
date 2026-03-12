<?php

require_once __DIR__ . '/env.php';

function app_base_url(): string
{
    $configured = env('APP_URL', '') ?: '';
    if ($configured !== '') {
        return rtrim($configured, '/');
    }

    $isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    $scheme = $isHttps ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

    return $scheme . '://' . $host;
}

function app_base_path(): string
{
    $scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    $directory = str_replace('\\', '/', dirname($scriptName));
    $projectRoot = str_replace('\\', '/', (string) (realpath(dirname(__DIR__)) ?: dirname(__DIR__)));
    $scriptFilename = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_FILENAME'] ?? ''));
    $scriptDirectory = $scriptFilename !== '' ? str_replace('\\', '/', dirname((string) (realpath($scriptFilename) ?: $scriptFilename))) : '';

    if ($scriptDirectory !== '') {
        $normalizedProjectRoot = strtolower(rtrim($projectRoot, '/'));
        $normalizedScriptDirectory = strtolower(rtrim($scriptDirectory, '/'));

        if ($normalizedProjectRoot !== '' && str_starts_with($normalizedScriptDirectory, $normalizedProjectRoot)) {
            $relativeDirectory = trim(substr($scriptDirectory, strlen($projectRoot)), '/');
            if ($relativeDirectory !== '') {
                $suffix = '/' . str_replace('\\', '/', $relativeDirectory);
                if (str_ends_with(strtolower($directory), strtolower($suffix))) {
                    $directory = substr($directory, 0, -strlen($suffix));
                }
            }
        }
    }

    if ($directory === '/' || $directory === '\\' || $directory === '.') {
        return '';
    }

    return rtrim($directory, '/');
}

function app_url(string $path = '/'): string
{
    return app_base_url() . app_path($path);
}

function app_path(string $path = '/'): string
{
    return app_base_path() . '/' . ltrim($path, '/');
}

function app_project_path(string $path = ''): string
{
    $root = dirname(__DIR__);
    if ($path === '') {
        return $root;
    }

    return $root . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
}
