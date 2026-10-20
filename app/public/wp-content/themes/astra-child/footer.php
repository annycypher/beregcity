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
      <?php
      $bc_vk = function_exists( 'bc_contact' ) ? bc_contact( 'vk' ) : '';
      $bc_tg = function_exists( 'bc_contact' ) ? bc_contact( 'telegram' ) : '';
      $bc_max = function_exists( 'bc_contact' ) ? bc_contact( 'max' ) : '';
      ?>
      <?php if ( '' !== $bc_vk || '' !== $bc_tg || '' !== $bc_max ) : ?>
      <div class="soc">
        <?php if ( '' !== $bc_vk ) : ?>
        <a href="<?php echo esc_url( $bc_vk ); ?>" aria-label="ВКонтакте" rel="noopener" target="_blank"><svg class="ico" viewBox="0 0 24 24"><path d="M3 8c1.5 6 5 9.5 9 10v-4c2 .5 3.5 2 4.5 4H20c-.7-3-2.5-5-4-6 1.5-1.5 3-3.5 3.5-6h-3.3c-.8 2.3-2.3 4.3-4.2 5V6H9v7C6.5 11.5 5 9.5 4.5 8z"/></svg></a>
        <?php endif; ?>
        <?php if ( '' !== $bc_tg ) : ?>
        <a href="<?php echo esc_url( $bc_tg ); ?>" aria-label="Телеграм" rel="noopener" target="_blank"><svg class="ico" viewBox="0 0 24 24"><path d="M21 4L3 11l5.5 2L10 19l3-3.5 4.5 3z"/><path d="M8.5 13L18 5.5"/></svg></a>
        <?php endif; ?>
        <?php if ( '' !== $bc_max ) : ?>
        <a href="<?php echo esc_url( $bc_max ); ?>" aria-label="MAX" rel="noopener" target="_blank"><svg class="ico" viewBox="0 0 24 24"><path d="M4 20V4h3.4l4.6 7 4.6-7H20v16h-3.2V9.8L12 16.6 7.2 9.8V20z"/></svg></a>
        <?php endif; ?>
      </div>
      <?php endif; ?>
    </div>
  </footer>
</div><!-- /.wrap -->

<?php wp_footer(); ?>
</body>
</html>
