<?php
header( 'Content-Type: application/json; charset=utf-8' );

$version = '2.4.6';

echo json_encode(
    array(
        'name'         => 'WP SitePilot AI',
        'slug'         => 'wp-sitepilot-ai',
        'version'      => $version,
        'description'  => 'Модульний плагін для нерухомості з GitHub-first ядром, самооновленням і менеджером модулів.',
        'author'       => 'Roman',
        'homepage'     => 'https://github.com/Rkhudzii/wp-sitepilot-ai',
        'package'      => 'https://codeload.github.com/Rkhudzii/wp-sitepilot-ai/zip/refs/heads/main',
        'requires'     => '6.0',
        'requires_php' => '7.4',
        'tested'       => '6.8',
        'sections'     => array(
            'description' => 'Оновлення ядра WP SitePilot AI завантажується прямо з GitHub-репозиторію без окремих релізів.',
            'changelog'   => 'Версія ' . $version . ': прибрано GitHub-запити зі сторінки налаштувань, прискорено менеджер модулів і стабілізовано автооновлення.',
        ),
    ),
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
);
