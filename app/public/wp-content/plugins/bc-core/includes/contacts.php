<?php
/**
 * Контакты сайта: страница настроек «Настройки сайта → Контакты» и вывод на фронте.
 *
 * Телефон показывается только при включённом чекбоксе «Показывать телефон».
 * В шаблонах телефонов больше нет — источник один: эти настройки.
 *
 * @package BC_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
exit;
}

/**
 * Страница настроек и поля.
 */
function bc_contacts_settings() {
if ( ! function_exists( 'acf_add_options_page' ) ) {
return;
}

acf_add_options_page(
array(
'page_title' => 'Настройки сайта',
'menu_title' => 'Настройки сайта',
'menu_slug'  => 'bc-site-settings',
'capability' => 'manage_options',
'icon_url'   => 'dashicons-admin-settings',
'position'   => 59,
'redirect'   => false,
)
);

acf_add_options_sub_page(
array(
'page_title'  => 'Контакты',
'menu_title'  => 'Контакты',
'parent_slug' => 'bc-site-settings',
'menu_slug'   => 'bc-site-contacts',
)
);

acf_add_local_field_group(
array(
'key'      => 'group_bc_contacts',
'title'    => 'Контакты сайта',
'fields'   => array(
array( 'key' => 'field_bc_phone', 'label' => 'Телефон', 'name' => 'phone', 'type' => 'text', 'instructions' => 'Например: +7 (391) 123-45-67. Выводится только при включённом чекбоксе ниже.' ),
array( 'key' => 'field_bc_phone_show', 'label' => 'Показывать телефон', 'name' => 'phone_show', 'type' => 'true_false', 'default_value' => 0, 'ui' => 1, 'instructions' => 'Выключено — телефона нет нигде на сайте.' ),
array( 'key' => 'field_bc_email', 'label' => 'Email', 'name' => 'email', 'type' => 'email' ),
array( 'key' => 'field_bc_telegram', 'label' => 'Telegram (ссылка)', 'name' => 'telegram', 'type' => 'url', 'instructions' => 'Полная ссылка: https://t.me/…' ),
array( 'key' => 'field_bc_vk', 'label' => 'ВКонтакте (ссылка)', 'name' => 'vk', 'type' => 'url', 'instructions' => 'Полная ссылка: https://vk.com/…' ),
array( 'key' => 'field_bc_address', 'label' => 'Адрес', 'name' => 'address', 'type' => 'text' ),
array( 'key' => 'field_bc_ad_text', 'label' => 'Текст для рекламодателей', 'name' => 'ad_text', 'type' => 'textarea', 'rows' => 3, 'instructions' => 'Короткая строка под телефоном/почтой, например: «Реклама и сотрудничество».' ),
),
'location' => array(
array(
array(
'param'    => 'options_page',
'operator' => '==',
'value'    => 'bc-site-contacts',
),
),
),
)
);
}
add_action( 'acf/init', 'bc_contacts_settings' );

/**
 * Значение настройки контактов.
 *
 * @param string $key Имя поля.
 * @return string
 */
function bc_contact( $key ) {
$value = get_field( $key, 'option' );
return is_string( $value ) ? trim( $value ) : '';
}

/**
 * Телефон для вывода: только если включён чекбокс и поле заполнено.
 *
 * @return string
 */
function bc_contact_phone() {
if ( ! get_field( 'phone_show', 'option' ) ) {
return '';
}
return bc_contact( 'phone' );
}

/**
 * Блок «Контакты» для главной и других страниц.
 *
 * @return string HTML (пустая строка, если ничего не заполнено).
 */
function bc_contacts_block() {
$phone   = bc_contact_phone();
$email   = bc_contact( 'email' );
$tg      = bc_contact( 'telegram' );
$vk      = bc_contact( 'vk' );
$address = bc_contact( 'address' );
$note    = bc_contact( 'ad_text' );

if ( '' === $phone && '' === $email && '' === $tg && '' === $vk && '' === $address ) {
return '';
}

ob_start();
?>
<div class="contacts">
<h3>Контакты</h3>
<?php if ( '' !== $phone ) : ?>
<div class="crow">
<svg class="ico" viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.8 19.8 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.12 4.18 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
<span><b><a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $phone ) ); ?>"><?php echo esc_html( $phone ); ?></a></b><?php if ( '' !== $note ) : ?><span><?php echo esc_html( $note ); ?></span><?php endif; ?></span>
</div>
<?php endif; ?>
<?php if ( '' !== $email ) : ?>
<div class="crow">
<svg class="ico" viewBox="0 0 24 24"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M22 6l-10 7L2 6"/></svg>
<span><b><a href="mailto:<?php echo esc_attr( $email ); ?>"><?php echo esc_html( $email ); ?></a></b></span>
</div>
<?php endif; ?>
<?php if ( '' !== $tg ) : ?>
<div class="crow">
<svg class="ico" viewBox="0 0 24 24"><path d="M21 4L3 11l5.5 2L10 19l3-3.5 4.5 3z"/><path d="M8.5 13L18 5.5"/></svg>
<span><b><a href="<?php echo esc_url( $tg ); ?>" rel="noopener" target="_blank">Telegram</a></b></span>
</div>
<?php endif; ?>
<?php if ( '' !== $vk ) : ?>
<div class="crow">
<svg class="ico" viewBox="0 0 24 24"><path d="M3 8c1.5 6 5 9.5 9 10v-4c2 .5 3.5 2 4.5 4H20c-.7-3-2.5-5-4-6 1.5-1.5 3-3.5 3.5-6h-3.3c-.8 2.3-2.3 4.3-4.2 5V6H9v7C6.5 11.5 5 9.5 4.5 8z"/></svg>
<span><b><a href="<?php echo esc_url( $vk ); ?>" rel="noopener" target="_blank">ВКонтакте</a></b></span>
</div>
<?php endif; ?>
<?php if ( '' !== $address ) : ?>
<div class="crow">
<svg class="ico" viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
<span><b><?php echo esc_html( $address ); ?></b><span>микрорайон Южный берег</span></span>
</div>
<?php endif; ?>
<a class="btn btn-terra" href="<?php echo esc_url( '' !== $email ? 'mailto:' . $email : home_url( '/reklama/' ) ); ?>" style="width:100%;justify-content:center">Написать нам</a>
</div>
<?php
return (string) ob_get_clean();
}