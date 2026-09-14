# Elementor A11y Fixer

**[EN]** Accessibility fixes that know Elementor markup: slider arrows get names, accordion titles declare their state, icon-only links can be named, images get alt. Every change is additive and previewable — non-Elementor pages are served byte-for-byte unchanged. No overlay, no JavaScript.

**[RU]** Правки доступности, которые знают разметку Elementor: стрелки слайдера получают имена, заголовки аккордеонов — состояние, ссылки-иконки — названия, картинки — alt. Каждая правка только добавляет атрибут и показывается в предпросмотре. Страницы без Elementor отдаются байт-в-байт. Без overlay и JavaScript.

🔗 [**Скачать бесплатно / Download free**](https://github.com/Yodzira/elementor-a11y-fixer/releases/latest/download/elementor-a11y-fixer.zip)

## Правки

| Правка | Риск | Что делает |
|---|---|---|
| Slider arrows | safe | aria-label «Previous slide» / «Next slide» стрелкам карусели |
| Accordion state | safe | aria-expanded на заголовках аккордеона/табов |
| Icon link names | ● risky | aria-label ссылкам-иконкам из URL (проверьте предпросмотр) |
| Image alt | safe | alt из медиабиблиотеки для картинок без него |

## Принципы

- **Zero overhead elsewhere**: страницы без разметки Elementor не парсятся вообще
- **Только добавление атрибутов** — теги, классы и текст нетронуты (Golden Master-тест)
- **Preview on homepage**: список изменений до включения любой правки
- Работает автономно; в паре с A11yFix — общий центр управления

## Установка / Install

1. Скачайте [`elementor-a11y-fixer.zip`](https://github.com/Yodzira/elementor-a11y-fixer/releases/latest/download/elementor-a11y-fixer.zip) (нужен Elementor)
2. WP-админка → **Плагины → Добавить новый → Загрузить плагин** → zip → Активировать
3. Меню **Elementor A11y** → Preview on homepage → включайте правки

## Требования / Requirements

- WordPress 6.0+ (протестировано до 7.1), PHP 7.4+, Elementor 3.x

## Качество / Quality

- PHPUnit (ядро): 6 тестов, 15 assertions ✅ (детектор, правки, Golden Master на Elementor-разметке)
- Интеграция на живом WP 7.1: 11/11 ✅
- Официальный Plugin Checker: 0 errors (release build) ✅

## Лицензия / License

GPL-2.0-or-later (совместимо с WordPress).

💰 **[Купить Pro / Buy Pro — 4 990 ₽/год](https://yodsira.duckdns.org/buy/elementor-a11y)** — лицензия на 1 сайт, 12 месяцев обновлений.
