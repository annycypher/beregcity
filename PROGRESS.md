# PROGRESS.md — «БерегСити» (beregcity)

Проект: сайт микрорайона Южный берег (Советский район, Красноярск).
Домены: основной — **beregcity.ru**; зеркало — **берегсити.рф** (301 на основной). Локально — **beregcity.local**.
Репозиторий: https://github.com/annycypher/beregcity (ветка `main`).
Иерархия правды: ПРОТОКОЛ.md → PROGRESS.md (этот) → DECISIONS.md → docs/SPRAVOCHNIK-FAKTOV.md → docs/ADMINGUIDE.md → design/*.html.

Легенда: ✅ сделано · ⏳ в работе/частично · ❌ не начато.

Последнее обновление: 02.10.2026

## Фаза 0. Документы (артефакты)
- [✅] ПРОТОКОЛ.md — правила, карта данных, этапы
- [✅] DECISIONS.md — журнал решений (D1–D20)
- [✅] PROGRESS.md — этот файл
- [✅] docs/SPRAVOCHNIK-FAKTOV.md — канон фактов (скелет v0.9, **НЕ вычитан владельцем**)
- [✅] docs/ADMINGUIDE.md — гайд владельца
- [✅] docs/features/cabinet.md — спецификация ЛК (**v1.1**: счета/роли/карта/дашборд — D25–D27)
- [✅] design/homepage.html — контракт дизайна главной (источник правды, D20)
- [❌] design/stories.html — вьюер сторис (нет)
- [❌] design/buttons.html — реестр кнопок (нет)
- [❌] docs/structure.md, docs/features/stories.md, docs/schema-sql-reference.sql — нет
- [✅] .clinerules — короткий указатель на документы

## Этап 1. Инициализация
- [✅] 1.1 Local: `beregcity.local`; Astra 4.14.0 + дочерняя `astra-child` (активна); Elementor и ACF активны
- [⏳] 1.2 Документы на месте + бренд-тексты в design (промокод «БС24», e-mail `hello@beregcity.ru`). Не хватает `design/stories.html`, `design/buttons.html`
- [✅] 1.3 Палитра в кастомизаторе Astra (глобальные цвета) — по `design/homepage.html` (D20)
- [✅] 1.4 ЧПУ «Название записи» — `/%postname%/`
- [✅] 1.5 Плагины: ACF, Elementor, Rank Math, Wordfence, All-in-One WP Migration, LiteSpeed Cache — установлены и активны
- [⏳] 1.6 `docs/ADMINGUIDE.md` создан; `docs/SPRAVOCHNIK-FAKTOV.md` есть, но **не вычитан владельцем**
- [✅] 1.7 Git init + `.gitignore` + первый коммит + GitHub (приватный, ветка `main`)
- [✅] 1.8 `WP_DEBUG_LOG` включён — `wp-content/debug.log` пишется

## Этап 1б. Базовая безопасность
- [⏳] 1б.1 Wordfence активен; 2FA администратора / скан по расписанию / лимит попыток входа — **включить владельцу** (ADMINGUIDE §10)
- [✅] 1б.2 XML-RPC off (403), `DISALLOW_FILE_EDIT`, wp-login переименован в `/bc-office-3184/`, users-endpoint закрыт (404), `?author` заблокирован
- [⏳] 1б.3 Honeypot + время заполнения + rate-limit — **отложено** до Этапа 3а (формы регистрации организаций ещё нет)
- [⏳] 1б.4 UpdraftPlus 1.26.8 установлен и активен; расписание + Яндекс.Диск — **настроить владельцу** (ADMINGUIDE §10)
- [✅] 1б.2-фикс: убран шум login в `debug.log` (в `security.php` переменные `$user_login`/`$error`/`$errors` заданы до подключения wp-login.php)

## Этап 2. Главная по референсу (design/homepage.html v3.3)
- [✅] 2.1a Каркас темы: тонкий `functions.php` + `includes/setup.php`, `includes/assets.php` (enqueue CSS/JS с `filemtime()`)
- [✅] 2.1b Ассеты: `assets/css/theme.css` (дизайн-система из `design/homepage.html` — перенесена полностью), `assets/js/theme.js` (слайдер + бургер)
- [✅] 2.2a Шапка: `header.php` по референсу (`.hdr`, `.burger`, `.logo`, `nav.main`, `.search`, `.hicons`, `.btn-cta`, `.mob-menu`; меню статикой)
- [✅] 2.2a+ : добавлена ссылка «Личный кабинет» → `/kabinet/` в `nav.main` и `.mob-menu` (D22 — новшество, в референсе её нет)
- [✅] 2.2a-фикс: шапка не ломается на средних ширинах (проверено замером headless Edge на 1440/1200/1100/1024/768/680/440/400/360): `.btn{white-space:nowrap}`, `.btn-cta{flex:none}`, `@media(max-width:1200px){.btn-cta{display:none}}`; возврат ~120px (6-й пункт меню D22 расширил строку за предел 1200): `.hdr-in` gap 16→11, `nav.main a` 9px13→9px9, `nav.main` gap→0, `.search` min 110→90, `.hicons a` 7px11→7px9, `.btn-cta` 12px22→12px18, `.burger{padding:0;flex:none}`; на ≤440 скрывается подстрока лого **только в шапке** (`.hdr .logo small`). Итог: горизонтального скролла нет, кнопка однострочная или скрыта, nav не наезжает на поиск. Коммит — после приёмки владельцем.
- [✅] 2.2b Подвал: `footer.php` по референсу (`footer .box` + `.logo` + `.f-links` + `.soc`; wp_footer() перед `</body>`)
- [✅] 2.2b-фикс: подвал **без копирайта** — единый теглайн «микрорайон Южный берег · Красноярск» в `.logo small` (D31); синхронизированы `header/footer` и референсы `design/homepage.html`, `design/catalog.html`
- [✅] 2.3 Главная: `front-page.php` — `[bc_slider]` + `.qrow` + 7 кружков (заглушка, якорь `bc_stories`) + `.ad/.ad-duo` (якорь `bc_banner`) + `[bc_cats]` + `.duo` (якорь `bc_news/bc_events`) + `.trio` (якорь `bc_map`) + `.cta`; `get_header()/get_footer()`
  - ⚠️ Примечание: данные на главной — заглушки, поэтому **финальное закрытие Этапа 2 по ТРИО отложено до Этапа 4.3** (живые данные из БД). Ответ владельца на ТРИО-вопрос «могу сам поменять текст слайда и состав категорий без ИИ?» на этом этапе — **«нет, пока это код-заглушки»**.
- [✅] 2.4 Плагин `bc-core`: шорткоды `[bc_slider]` (hero-слайдер), `[bc_cats]` (12 категорий); плагин активен
- [✅] 2.4-тест: временная страница с шорткодами (ID 7) удалена после приёмки
- [✅] Приёмка Этапа 2 владельцем (десктоп 1200px + 360px, JS/консоль, «Личный кабинет») — пройдена 02.10.2026
- [✅] 2.5-а Меню → `wp_nav_menu`: walker `BC_Nav_Walker` (голые `<a>`, без `<ul>/<li>`), 2 области `bc_primary`/`bc_mobile`, меню «Шапка»/«Бургер» созданы и назначены; fallback — статичные ссылки
- [✅] 2.5-б Favicon: inline SVG через `wp_head` (`setup.php`, `bc_favicon_svg`) — **форма владельца** из `design/favicon.svg` (знак + волна) под общий стиль сайта: терракотовый градиент `#dda38b→#c48067` + белый знак; не дублируется, если задан WP Site Icon
- [✅] 2.5-в Логотип/подвал: знак (форма владельца из `design/favicon.svg`) перенесён в шапку (22px) и подвал (20px) через `bc_logo_mark()`; копирайт в подвале убран отовсюду, включая референс (по решению владельца)
## Этап 3. Каталог организаций (3а кабинет, 3б снипеты/SEO, 3в 2FA кабинета)
- [✅] 3.1 Данные `bc-core`: CPT `organizations` (rewrite `/katalog/organization/`, `has_archive` `/katalog/`), таксономии `bc_cat`/`features` (34 снипета, 6 групп), ACF-поля (17, ключи `field_bc_*`), сид терминов (D32/D33)
- [✅] 3.2 Списки: `archive-organizations.php` (`/katalog/`) + `taxonomy-bc_cat.php` (`/katalog/{cat}/`) + `template-parts/org-card.php` — **вёрстка по `design/catalog.html`**; стили каталога/карточки в `theme.css`
- [✅] 3.2б Карточка организации: `single-organizations.php` — **вёрстка по `design/org-card.html`** (галерея, `.org-side`, описание, сторис-заглушка, отзывы-заглушка, карта-заглушка, «Похожие рядом», callbar)
- [✅] 3.2а Автостатус графика на карточке: `bc_schedule_status()` (`includes/schedule.php`) — открыто/закрыто/«откроется в», особые дни приоритетны, таймзона сайта (задана `Asia/Krasnoyarsk`) — §19 cabinet.md
- [✅] 3.3a Программатик-rewrite `/katalog/{cat}/{feature}/` + ворота D14 (≥3 организации, 404 пустых) — `bc-core/includes/rewrite.php`
- [✅] 3.3b `bc_filters` (панель features + живые счётчики через SQL) + `bc_sort` — интегрировано в `taxonomy-bc_cat.php` и `archive-organizations.php`
- [✅] 3.3c `bc_grid`: тарифная логика состава карточки (free/стандарт/премиум — `bc_plan()` в `plans.php`, ribbon/vbadge/снипеты/описание/`.is-premium`) + ItemList JSON-LD
- [✅] 3.3d `bc_pager` (точная пагинация) + BreadcrumbList JSON-LD + программатик-интро (D14: h1/интро для `/katalog/{cat}/{feature}/`)
- [✅] 3.3e Фильтры: GET-форма (не AJAX), D34 (AND в группе / OR между группами), `bc_catalog_tax_query`, canonical + noindex (SEO head), JS `#ftoggle` (5 строк)
- [✅] 3.4 Тарифы: `BC_Plans` класс (лимиты D15), сортировка premium↑ (`bc_plan` orderby + CASE), бейджи ribbon/vbadge (в 3.3c)
- [ ] 3.4а QR-код карточки в ЛК: генерация PHP, PNG-выгрузка, шаблон наклейки A6 — §18 cabinet.md
- [✅] 3.5 Админ-экраны: меню «БерегСити» + дашборд (§16 счётчики), «Утверждение карточек» (D24: очередь pending, предпросмотр, одобрить/доработка/отклонить с причиной, по одной), «Платежи и счета» (D25 — заглушка до 3а)
- [⏳] 3.6 Тест: 3 организации разных тарифов — владелец в админке (ТРИО) — **ОТЛОЖЕНО владельцем (05.10.2026), напомнить позже**
- [ ] 3а.7 Прогресс заполнения карточки в ЛК (кольцо + чек-лист недостающего) — §17 cabinet.md
  - ⚠️ **Ограничения Этапа 3 (обязательно, Патч 3):** поведенческие фичи `cabinet.md` v1.1 §17–19 обязательны — автостатус графика на карточке (**3.2а**), QR-код в ЛК (**3.4а**), прогресс заполнения карточки (**3а.7**). Блок **«Похожие рядом»** на карточке — живая выборка: та же категория, исключая текущую организацию, максимум 3; при недостатке — секция скрывается.
## Этап 3а. Личный кабинет организаций
- [✅] 3а.1 Регистрация + роль: `org_manager` (без wp-admin, админ-бар скрыт), `/kabinet/register` — nonce + honeypot + время + rate-limit 5/час/IP + email-верификация (draft→pending) — `includes/roles.php` + `includes/register.php`
- [✅] 3а.2 Каркас ЛК: `/kabinet/` (гость→вход, org_manager→кабинет), вкладки (отдельные URL), «Моя карточка» acf_form + прогресс §17 — `lk-routes.php` + `page-kabinet.php` + `template-parts/lk-card.php` + стили ЛК
- [✅] 3а.3 `BC_Plans`: `activate()` (единая точка, plan_until + период), `start_trial()` (+7 дней), `price()` (скидки периодов), проверка лимитов ДО сохранения (`acf/validate_save_post`)
- [✅] 3а.4 Админ: платежи (D25 полный: очередь created → Подтвердить → `BC_Plans::activate()`, Отклонить с причиной + письмо), таблица `wp_bc_payments`, счётчики дашборда реальные (pending + платежи)
- [✅] 3а.5 Счета D25: автосоздание (`bc_create_invoice`), шаблон `bc-invoice` (лого, реквизиты-заглушки §5, «НДС не облагается»), `@media print`, письмо HTML, публичная ссылка с токеном (`/kabinet/invoice/{token}/`)
- [✅] 3а.6 Статистика: просмотры (cookie-дедуп/сутки) + клики телефон/сайт/мессенджеры (редирект `/go/{id}/{type}/`), витрина 7/30 (`.stat`/`.bars14`)
- [✅] 3а.7 QR: чистый PHP-генератор (byte/EC L/маска 0, версии 1–10), SVG + PNG (GD, `/qr/{id}/`), блок `.qr-block` в ЛК (тариф Стандарт+)

## Этап 4. Новости + афиша
- [✅] 4.1 Данные: CPT `news` (тип/is_pinned/erid) + CPT `events` (дата/место/цена/is_free) + ACF — `cpt-news.php`
- [✅] 4.2 Шаблоны: `archive-news.php`/`single-news.php` + `archive-events.php`/`single-events.php` (фильтр по датам предстоящие/прошедшие/все) + JSON-LD Event
- [✅] 4.3 Живые блоки главной: «Новости района» (3 последних news) и «Афиша» (3 предстоящих events) из БД в `front-page.php`
- [✅] 4.4 Вкладка ЛК «Акции»: создание акции (news тип promo) + лимит тарифа (`BC_Plans::can` promo) + модерация (pending→publish). Гутенберг-паттерны статей — отложено (нужны паттерны от владельца)
## Этап 5. Сторис
- [✅] 5.1 Данные: CPT `stories` + ACF (repeater слайдов: image/caption/btn_text/link_url/is_ad/erid, show_until, sort_order) — `cpt-stories.php`
- [✅] 5.2 Вывод: `[bc_stories]`/`[bc_stories org="ID"]` (данные STORIES) + вьюер `stories.js` (5.3 КБ ≤40, bc_seen, жесты/автоплей 6с) + стили вьюера
- [✅] 5.3 Админ: подменю «Сторис» — модерация (очередь pending, одобрить с erid-проверкой / отклонить с причиной + письмо, предпросмотр миниатюрами)
- [✅] 5.4 ЛК: вкладка «Мои стории» — режимы ВЫКЛ/Заявки/Самостоятельные (заявка → pending + письмо; self → acf_form slides), список со статусами
- [✅] 5.5 Интеграция: `[bc_stories]` на главной (замена 7 статичных кружков) + `[bc_stories org="ID"]` на карточке
## Этап 6. Баннеры + формы + карта (6а платежи) — ❌ не начато
## Этап 7. Скорость и SEO
- [ ] 7.x Отключить эмодзи-скрипт WordPress (внешний запрос на `s.w.org`) — требование D11
## Этап 8. Деплой (8б харднинг)
- [ ] 8.x Правообладание: добавить в **Политику конфиденциальности** (копирайт в подвале убран — D31); юр. оговорка на Этап 8

## Следующий шаг
Шаг **тест ТРИО (5)** — владелец: создать сторию (2 слайда) в админке, проверить вьюер/жесты, путь организации (заявка → модерация → карточка).
