<?php
/**
 * Подвал сайта (Этап 2.2b) — разметка 1:1 по design/homepage.html.
 *
 * Классы = контракт (ПРОТОКОЛ). В референсе <footer> расположен ВНУТРИ
 * контейнера `.wrap`, который открывает контентный шаблон (front-page.php),
 * поэтому подвал закрывает `.wrap`. Далее — wp_footer() перед </body>
 * (критично: иначе не выведутся скрипты, подключённые в подвал, вкл. theme.js).
 *
 * @package Astra_Child
 */

?>
  <!-- Подвал -->
  <footer>
    <div class="box">
      <a class="logo" href="/">
        <span class="mark"><?php echo bc_logo_mark( 20 ); ?></span>
        <span><b style="font-size:16px;font-weight:600">БерегСити</b><small>микрорайон Южный берег · Красноярск</small></span>
      </a>
      <div class="f-links">
        <a href="/sitemap">Карта сайта</a>
        <a href="/privacy">Политика конфиденциальности</a>
        <a href="/terms">Пользовательское соглашение</a>
        <a href="/reklama">Рекламодателям</a>
      </div>
      <div class="soc">
        <?php /* ФАКТ-ПРОВЕРКА: реальные соцсети и адреса — уточнить в СПРАВОЧНИК §9; пока заглушки "#". */ ?>
        <a href="#" aria-label="ВКонтакте"><svg class="ico" viewBox="0 0 24 24"><path d="M3 8c1.5 6 5 9.5 9 10v-4c2 .5 3.5 2 4.5 4H20c-.7-3-2.5-5-4-6 1.5-1.5 3-3.5 3.5-6h-3.3c-.8 2.3-2.3 4.3-4.2 5V6H9v7C6.5 11.5 5 9.5 4.5 8z"/></svg></a>
        <a href="#" aria-label="Телеграм"><svg class="ico" viewBox="0 0 24 24"><path d="M21 4L3 11l5.5 2L10 19l3-3.5 4.5 3z"/><path d="M8.5 13L18 5.5"/></svg></a>
        <a href="#" aria-label="WhatsApp"><svg class="ico" viewBox="0 0 24 24"><path d="M21 12a9 9 0 0 1-13.3 7.9L3 21l1.2-4.6A9 9 0 1 1 21 12z"/><path d="M8.5 9.5c.5 3 2.5 5 5.5 5.5l1-1.5 2 1"/></svg></a>
      </div>
    </div>
  </footer>
</div><!-- /.wrap -->

<?php wp_footer(); ?>
</body>
</html>
