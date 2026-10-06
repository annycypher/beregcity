<?php
/** Front page — сборка по design/homepage.html v3.3. Классы = контракт. */
get_header();
?>
<?php echo do_shortcode( '[bc_slider]' ); ?>

<div class="wrap">

  <!-- Быстрые карточки -->
  <?php /* bc_quickcards: 4 карточки-заглушки (ссылки статикой) — оживление позже */ ?>
  <div class="qrow">
    <a class="qcard" href="/katalog">
      <span class="qic g"><svg class="ico" viewBox="0 0 24 24"><circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c0-3.5 3-5.5 6.5-5.5s6.5 2 6.5 5.5"/><circle cx="17" cy="9" r="2.5"/><path d="M16 14.5c3 .3 5.5 2 5.5 5"/></svg></span>
      <span><b>Жителям</b><span>Каталог, объявления и полезные телефоны района</span></span>
      <svg class="ico arr" style="width:18px;height:18px" viewBox="0 0 24 24"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
    </a>
    <a class="qcard" href="/reklama">
      <span class="qic t"><svg class="ico" viewBox="0 0 24 24"><rect x="3" y="7" width="18" height="13" rx="2"/><path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg></span>
      <span><b>Бизнесу</b><span>Тарифы, реклама и продвижение организации</span></span>
      <svg class="ico arr" style="width:18px;height:18px" viewBox="0 0 24 24"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
    </a>
    <a class="qcard" href="/afisha">
      <span class="qic g"><svg class="ico" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="17" rx="3"/><path d="M8 2v4M16 2v4M3 10h18"/></svg></span>
      <span><b>События</b><span>Афиша мероприятий на каждые выходные</span></span>
      <svg class="ico arr" style="width:18px;height:18px" viewBox="0 0 24 24"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
    </a>
    <a class="qcard" href="/spravochnik">
      <span class="qic t"><svg class="ico" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M9 13h6M9 17h6"/></svg></span>
      <span><b>Справочник</b><span>Телефоны УК, служб и расписания</span></span>
      <svg class="ico arr" style="width:18px;height:18px" viewBox="0 0 24 24"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
    </a>
  </div>

  <!-- Сторис -->
  <?php /* bc_stories: заменить на [bc_stories] на Этапе 5 */ ?>
  <div class="stories">
    <a class="story" href="/stories/novosti"><span class="ring"><span class="in"><svg class="ico" viewBox="0 0 24 24"><path d="M4 22h16a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v16a2 2 0 0 1-4 0V9"/><path d="M12 6h6M12 10h6M12 14h6"/></svg></span></span><b>Новости</b></a>
    <a class="story" href="/stories/akcii"><span class="ring"><span class="in"><svg class="ico" viewBox="0 0 24 24"><path d="M20.59 13.41L11 3.83A2 2 0 0 0 9.59 3.24H4a1 1 0 0 0-1 1v5.59a2 2 0 0 0 .59 1.41l9.58 9.59a2 2 0 0 0 2.83 0l4.59-4.59a2 2 0 0 0 0-2.83z"/><circle cx="7.5" cy="7.5" r="1"/></svg></span></span><b>Акции</b></a>
    <a class="story" href="/stories/novye"><span class="ring"><span class="in"><svg class="ico" viewBox="0 0 24 24"><path d="M12 2l2.4 6.2L21 9l-5 4.1 1.6 6.4L12 15.8 6.4 19.5 8 13.1 3 9l6.6-.8z"/></svg></span></span><b>Новые места</b></a>
    <a class="story" href="/stories/afisha"><span class="ring"><span class="in"><svg class="ico" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="17" rx="3"/><path d="M8 2v4M16 2v4M3 10h18"/></svg></span></span><b>Афиша</b></a>
    <a class="story" href="/stories/obyavleniya"><span class="ring"><span class="in"><svg class="ico" viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg></span></span><b>Объявления</b></a>
    <a class="story" href="/stories/zhkh"><span class="ring"><span class="in"><svg class="ico" viewBox="0 0 24 24"><path d="M17.5 19a4.5 4.5 0 1 0-.44-8.98A7 7 0 1 0 4 14.9"/><path d="M12 12v9M8.5 17.5L12 21l3.5-3.5"/></svg></span></span><b>ЖКХ</b></a>
    <a class="story" href="/stories/transport"><span class="ring"><span class="in"><svg class="ico" viewBox="0 0 24 24"><rect x="4" y="3" width="16" height="14" rx="3"/><path d="M4 10h16M8 21l1.5-4M16 21l-1.5-4"/></svg></span></span><b>Транспорт</b></a>
  </div>

  <!-- Реклама -->
  <?php /* bc_banner: на Этапе 6.1 заменить на [bc_banner zone="wide"] и [bc_banner zone="duo-1|duo-2"] */ ?>
  <div class="ad ad-wide">Баннер 970×90 — широкий слот</div>
  <div class="ad-duo">
    <div class="ad">Баннер 470×120 — слот 1</div>
    <div class="ad">Баннер 470×120 — слот 2</div>
  </div>

  <!-- Категории -->
  <?php echo do_shortcode( '[bc_cats]' ); ?>

  <!-- Новости + Афиша -->
  <?php /* bc_news / bc_events: живые данные из БД — Этап 4.3 */ ?>
  <div class="duo">
    <div class="panel">
      <h2 class="sec">Новости района <a class="pill" href="/news">Все новости</a></h2>
      <?php
      $bc_news = new WP_Query( array( 'post_type' => 'news', 'post_status' => 'publish', 'posts_per_page' => 3 ) );
      if ( $bc_news->have_posts() ) :
        while ( $bc_news->have_posts() ) : $bc_news->the_post();
          $bc_ntype = get_field( 'news_type' );
      ?>
      <article class="news-item">
        <div class="thumb"><?php if ( has_post_thumbnail() ) { the_post_thumbnail( 'medium' ); } else { ?><svg class="ico" viewBox="0 0 24 24"><path d="M18 8h1a4 4 0 0 1 0 8h-1"/><path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"/></svg><?php } ?></div>
        <div>
          <span class="tag<?php echo ( $bc_ntype && 'news' !== $bc_ntype ) ? ' s' : ''; ?>"><?php echo esc_html( $bc_ntype ? $bc_ntype : 'Новость' ); ?></span>
          <time><?php echo get_the_date( 'd.m.Y' ); ?></time>
          <h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
          <p><?php echo esc_html( wp_trim_words( get_the_excerpt(), 18 ) ); ?></p>
        </div>
      </article>
      <?php
        endwhile;
        wp_reset_postdata();
      else :
        echo '<p class="found">Новостей пока нет.</p>';
      endif;
      ?>
    </div>

    <div class="panel">
      <h2 class="sec">Афиша событий <a class="pill" href="/afisha">Все события</a></h2>
      <?php
      $bc_events = new WP_Query(array('post_type'=>'events','post_status'=>'publish','posts_per_page'=>3,'meta_key'=>'event_date','orderby'=>'meta_value','order'=>'ASC','meta_query'=>array(array('key'=>'event_date','value'=>current_time('Y-m-d'),'compare'=>'>=','type'=>'DATE'))));
      if ($bc_events->have_posts()) : while ($bc_events->have_posts()) : $bc_events->the_post();
        $bc_d=get_field('event_date'); $bc_t=get_field('event_time'); $bc_pl=get_field('event_place'); $bc_free=get_field('is_free'); $bc_price=get_field('event_price');
        $bc_months=array('','янв','фев','мар','апр','май','июн','июл','авг','сен','окт','ноя','дек'); $bc_m=(int)date('n',strtotime($bc_d));
      ?>
      <article class="ev">
        <div class="dbox"><span><?php echo $bc_d ? date('d', strtotime($bc_d)) : ''; ?></span><small><?php echo isset($bc_months[$bc_m]) ? $bc_months[$bc_m] : ''; ?></small></div>
        <div>
          <h4><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h4>
          <div class="meta">
            <i><?php echo $bc_t ? esc_html($bc_t) : ''; ?><?php echo $bc_free ? ' · бесплатно' : ($bc_price ? ' · '.esc_html($bc_price) : ''); ?></i>
            <i><?php echo esc_html($bc_pl); ?></i>
          </div>
        </div>
        <div class="thumb"><?php if (has_post_thumbnail()) { the_post_thumbnail('medium'); } else { ?><svg class="ico" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="17" rx="3"/><path d="M8 2v4M16 2v4M3 10h18"/></svg><?php } ?></div>
      </article>
      <?php endwhile; wp_reset_postdata(); else : echo '<p class="found">Событий пока нет.</p>'; endif; ?>
    </div><a class="green-banner" href="/dobavit-sobytie">
        <svg class="ico" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M8 12.5l2.5 2.5L16 9.5"/></svg>
        <span><b>Организуете событие в районе?</b>
        <span>Анонс бесплатно — афиша живёт от ваших новостей</span></span>
        <svg class="ico arr" style="width:18px;height:18px" viewBox="0 0 24 24"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
      </a>
    </div>
  </div>

  <!-- О районе + карта + контакты -->
  <div class="trio">
    <div>
      <h3>О микрорайоне</h3>
      <p>Южный берег — растущий микрорайон Советского района Красноярска: современные дворы, школы и детские сады, набережная и вся необходимая инфраструктура для комфортной жизни.</p>
      <a class="btn btn-glass" href="/o-raione">Подробнее о районе</a>
    </div>
    <?php /* bc_map: Яндекс.Карта под флагом BC_MAP_ON (D11); сейчас SVG-заглушка из референса */ ?>
    <div class="map-ph">
      <svg viewBox="0 0 400 260" preserveAspectRatio="none">
        <path d="M150 40 L260 30 L310 90 L290 180 L210 230 L130 190 L110 100 Z"
              fill="rgba(201,141,117,.12)" stroke="rgba(176,117,92,.45)" stroke-width="2"/>
        <text x="205" y="135" font-size="13" fill="#b0755c" text-anchor="middle" font-family="Arial">Южный берег</text>
      </svg>
      <a class="btn btn-glass" style="position:absolute;bottom:14px;left:14px" href="/karta">Открыть карту</a>
    </div>
    <div class="contacts">
      <h3>Контакты</h3>
      <div class="crow">
        <svg class="ico" viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.8 19.8 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.12 4.18 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
        <span><b>+7 (000) 000-00-00</b><span>реклама и сотрудничество [ФАКТ-ПРОВЕРКА]</span></span>
      </div>
      <div class="crow">
        <svg class="ico" viewBox="0 0 24 24"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M22 6l-10 7L2 6"/></svg>
        <span><b>hello@beregcity.ru</b><span>ответим в течение дня</span></span>
      </div>
      <div class="crow">
        <svg class="ico" viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
        <span><b>Красноярск, Советский район</b><span>микрорайон Южный берег</span></span>
      </div>
      <a class="btn btn-terra" href="/contacts" style="width:100%;justify-content:center">Написать нам</a>
    </div>
  </div>

  <!-- CTA бизнес -->
  <div class="cta">
    <div>
      <h3>У вас бизнес на Южном берегу?</h3>
      <p>Бесплатная карточка в каталоге, платные пакеты продвижения и реклама на сайте.</p>
    </div>
    <a class="btn btn-terra" href="/dobavit">Добавить организацию →</a>
  </div>

<?php // .wrap закрывается в footer.php ?>
<?php get_footer(); ?>


