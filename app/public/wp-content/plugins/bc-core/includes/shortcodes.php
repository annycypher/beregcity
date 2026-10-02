<?php
/**
 * Шорткоды проекта «БерегСити» (Этап 2.4).
 *
 * Разметка 1:1 по design/homepage.html. Каждый шорткод ВОЗВРАЩАЕТ строку
 * (ob_start()/ob_get_clean()), ничего не печатает — иначе ломается порядок
 * вёрстки на front-page. Данные — заглушки; живые данные из БД — Этап 4.3.
 *
 * @package BC_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * [bc_slider] — hero-слайдер главной: 3 слайда-заглушки, стрелки, точки.
 *
 * @return string HTML.
 */
function bc_shortcode_slider() {
	ob_start();
	?>
<div class="hero wrap">
  <div class="track" id="track">
    <div class="slide sl-photo">
      <span class="tag-ad">Реклама</span>
      <div>
        <span class="chip-pill">Всё микрорайона — в одном месте</span>
        <h1>Южный берег — район,<br>где всё рядом</h1>
        <p>Каталог организаций, новости и афиша событий — адреса, телефоны и часы работы в двух кликах от дома.</p>
        <div class="row">
          <a class="btn btn-terra" href="/katalog">Открыть каталог
            <svg class="ico" style="width:17px;height:17px" viewBox="0 0 24 24"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a>
          <a class="btn btn-glass" href="/news">Новости района</a>
        </div>
      </div>
    </div>
    <div class="slide sl-sage">
      <div>
        <span class="chip-pill">Выходные рядом с домом</span>
        <h2>Афиша микрорайона<br>на субботу и воскресенье</h2>
        <p>Ярмарка, мастер-классы и дворовый концерт — полное расписание событий Южного берега.</p>
        <div class="row"><a class="btn btn-sage" href="/afisha">Смотреть афишу</a></div>
      </div>
    </div>
    <div class="slide sl-glasscard">
      <div>
        <span class="chip-pill">Местному бизнесу</span>
        <h2>Ваша организация —<br>в каталоге района</h2>
        <p>Бесплатная карточка: адрес, телефон и график увидят все жители. Платные пакеты — продвижение и акции.</p>
        <div class="row"><a class="btn btn-terra" href="/dobavit">Добавить организацию</a></div>
      </div>
    </div>
  </div>
  <div class="arrows">
    <button class="prev" aria-label="Назад"><svg class="ico" viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg></button>
    <button class="next" aria-label="Вперёд"><svg class="ico" viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg></button>
  </div>
  <div class="dots"><span class="dot on"></span><span class="dot"></span><span class="dot"></span></div>
</div>
	<?php
	return ob_get_clean();
}
add_shortcode( 'bc_slider', 'bc_shortcode_slider' );

/**
 * [bc_cats] — сетка категорий каталога: 12 карточек-заглушек.
 *
 * @return string HTML.
 */
function bc_shortcode_cats() {
	ob_start();
	?>
<div class="cats">
  <?php /* ФАКТ-ПРОВЕРКА: живые счётчики организаций — Этап 4.3 (числа ниже — заглушки макета). */ ?>
    <a class="cat" href="/katalog/eda"><span class="chip"><svg class="ico" viewBox="0 0 24 24"><path d="M18 8h1a4 4 0 0 1 0 8h-1"/><path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"/></svg></span><b>Еда</b><span>24</span></a>
    <a class="cat" href="/katalog/medicina"><span class="chip"><svg class="ico" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 8v8M8 12h8"/></svg></span><b>Медицина</b><span>15</span></a>
    <a class="cat" href="/katalog/ucheba"><span class="chip"><svg class="ico" viewBox="0 0 24 24"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg></span><b>Учёба</b><span>11</span></a>
    <a class="cat" href="/katalog/deti"><span class="chip"><svg class="ico" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2M9 9h.01M15 9h.01"/></svg></span><b>Дети</b><span>18</span></a>
    <a class="cat" href="/katalog/avto"><span class="chip"><svg class="ico" viewBox="0 0 24 24"><rect x="1" y="4" width="15" height="12" rx="2"/><path d="M16 9h4l3 3v4h-7z"/><circle cx="5.5" cy="18.5" r="2"/><circle cx="18.5" cy="18.5" r="2"/></svg></span><b>Авто</b><span>9</span></a>
    <a class="cat" href="/katalog/magaziny"><span class="chip"><svg class="ico" viewBox="0 0 24 24"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><path d="M3 6h18M16 10a4 4 0 0 1-8 0"/></svg></span><b>Магазины</b><span>32</span></a>
    <a class="cat" href="/katalog/dekor"><span class="chip"><svg class="ico" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg></span><b>Декор</b><span>7</span></a>
    <a class="cat" href="/katalog/remont"><span class="chip"><svg class="ico" viewBox="0 0 24 24"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg></span><b>Ремонт</b><span>21</span></a>
    <a class="cat" href="/katalog/krasota"><span class="chip"><svg class="ico" viewBox="0 0 24 24"><circle cx="6" cy="6" r="3"/><circle cx="6" cy="18" r="3"/><path d="M20 4L8.12 15.88M14.47 14.48L20 20M8.12 8.12L12 12"/></svg></span><b>Красота</b><span>13</span></a>
    <a class="cat" href="/katalog/uslugi"><span class="chip"><svg class="ico" viewBox="0 0 24 24"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg></span><b>Услуги</b><span>26</span></a>
    <a class="cat" href="/katalog/razvlecheniya"><span class="chip"><svg class="ico" viewBox="0 0 24 24"><rect x="2" y="2" width="20" height="20" rx="2"/><path d="M7 2v20M17 2v20M2 12h20M2 7h5M2 17h5M17 7h5M17 17h5"/></svg></span><b>Развлечения</b><span>10</span></a>
    <a class="cat" href="/katalog/nedvizhimost"><span class="chip"><svg class="ico" viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><path d="M9 22V12h6v10"/></svg></span><b>Недвижимость</b><span>6</span></a>
</div>
	<?php
	return ob_get_clean();
}
add_shortcode( 'bc_cats', 'bc_shortcode_cats' );
