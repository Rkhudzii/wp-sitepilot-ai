<?php
require_once __DIR__ . '/recrm-xml-import.php';

header('Content-Type: application/json; charset=utf-8');

echo json_encode([
    'name'         => 'WP SitePilot AI',
    'slug'         => 'wp-sitepilot-ai',
    'version'      => WPSP_AI_VERSION,
    'description'  => 'Модульний плагін для нерухомості з GitHub-first ядром, самооновленням і менеджером модулів.',
    'author'       => 'Roman',
    'homepage'     => 'https://github.com/Rkhudzii/wp-sitepilot-ai',
    'package'      => 'https://github.com/Rkhudzii/wp-sitepilot-ai/releases/download/v' . WPSP_AI_VERSION . '/wp-sitepilot-ai.zip',
    'requires'     => '6.0',
    'requires_php' => '7.4',
    'tested'       => '6.8',
    'sections'     => [
        'description' => 'Оновлення ядра WP SitePilot AI завантажується прямо з GitHub-репозиторію.',
        'changelog'   => 'Версія ' . WPSP_AI_VERSION . ': оновлення ядра плагіна.'
    ]
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);