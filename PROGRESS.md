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
- [✅] docs/features/cabinet.md — спецификация личного кабинета (v1.0)
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

## Этап 2. Главная по референсу — ❌ не начато
## Этап 3. Каталог (3а кабинет, 3б снипеты/SEO, 3в 2FA кабинета) — ❌ не начато
## Этап 4. Новости + афиша — ❌ не начато
## Этап 5. Сторис — ❌ не начато
## Этап 6. Баннеры + формы + карта (6а платежи) — ❌ не начато
## Этап 7. Скорость и SEO — ❌ не начато
## Этап 8. Деплой (8б харднинг) — ❌ не начато

## Следующий шаг
Этап 1б по коду закрыт. Владельцу: включить 2FA / скан по расписанию / лимит попыток входа в Wordfence и настроить UpdraftPlus (Яндекс.Диск, расписание) — см. ADMINGUIDE §10. Далее — Этап 2 «Главная по референсу» (`design/homepage.html`).
