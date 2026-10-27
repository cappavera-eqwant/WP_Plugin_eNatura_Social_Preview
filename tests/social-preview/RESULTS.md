# Проверки 1.0.0 — 27 октября 2026

- PHP 7.4 grammar parse через php-parser: основной файл, uninstall.php, test.php — PASS.
- Изолированный PHP 8.3.33 (WordPress php-wasm 3.1.61), test.php с заглушками API: 23 проверки — PASS. Проверены абсолютные/опасные URL, canonical и данные записи, escaping, отсутствие выдуманного изображения, пароль/draft/search/preview/blog_public, главная, приоритет брендовой картинки над миниатюрой, large/summary, слишком маленькая картинка, режимы off/auto/on, Yoast, работа типа product без WooCommerce, getters товара, отсутствие цен/наличия, удаление script.
- ZIP: целостность CRC, один корневой каталог enatura-social-preview, только исходники и README — PASS.

Повторение на машине с PHP: из корня проекта `php tests/social-preview/test.php`. Заглушки намеренно не моделируют полный WordPress.

Не выполнены: настоящий WordPress/WooCommerce; активация/удаление и форма settings/nonces/capabilities в браузере; интеграция с реальными SEO-плагинами (проверен один Yoast-маркер, остальные детекторы проверены чтением кода); HTTP redirect/canonical архива и пагинации в разных permalink-режимах; реальный wp_head темы; медиа/CDN/robots/WAF; multisite; карточки Telegram/VK/WhatsApp/Facebook/X и их кеши. Приложен ручной список в README. Изолированные тесты не доказывают эти интеграции.

Локально исследованы README темы 1.0.35 и PHP-модули настроек/магазина/соцсетей. Дампа реальной БД и действующего WordPress в рабочей папке нет; секретные конфигурации/подключения не использовались. Тема не изменялась. Плагин не устанавливался и не публиковался.
