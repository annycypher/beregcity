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
bc_contacts_native_admin(); // ACF Free: своя страница «Настройки сайта → Контакты».
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
array( 'key' => 'field_bc_contact_max', 'label' => 'MAX (ссылка)', 'name' => 'max', 'type' => 'url', 'instructions' => 'Полная ссылка: https://max.ru/…' ),
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
$value = get_option( 'options_' . $key, null ); // ACF хранит опции как options_{name}.
if ( null === $value && function_exists( 'get_field' ) ) {
$value = get_field( $key, 'option' );
}
return is_string( $value ) ? trim( $value ) : '';
}

/**
 * Телефон для вывода: только если включён чекбокс и поле заполнено.
 *
 * @return string
 */
function bc_contact_phone() {
if ( ! get_option( 'options_phone_show' ) ) {
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
$max     = bc_contact( 'max' );
$vk      = bc_contact( 'vk' );
$address = bc_contact( 'address' );
$note    = bc_contact( 'ad_text' );

if ( '' === $phone && '' === $email && '' === $tg && '' === $max && '' === $vk && '' === $address ) {
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
<?php if ( '' !== $max ) : ?>
<div class="crow">
<svg class="ico" viewBox="0 0 24 24"><path d="M4 20V4h3.4l4.6 7 4.6-7H20v16h-3.2V9.8L12 16.6 7.2 9.8V20z"/></svg>
<span><b><a href="<?php echo esc_url( $max ); ?>" rel="noopener" target="_blank">MAX</a></b></span>
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

/**
 * ACF Free: нативная страница настроек «Настройки сайта → Контакты».
 * Значения хранятся как options_{name} — их же читает bc_contact(), поэтому
 * при появлении ACF Pro (Options Pages) данные останутся на месте.
 */
function bc_contacts_native_admin() {
add_action( 'admin_menu', 'bc_contacts_native_menu' );
add_action( 'admin_init', 'bc_contacts_native_register' );
}

/**
 * Поля страницы контактов: имя => параметры.
 *
 * @return array
 */
function bc_contacts_native_fields() {
return array(
'phone'      => array( 'label' => 'Телефон', 'type' => 'text', 'hint' => 'Например: +7 (391) 123-45-67. Выводится только при включённом чекбоксе ниже.', 'sanitize' => 'sanitize_text_field' ),
'phone_show' => array( 'label' => 'Показывать телефон', 'type' => 'checkbox', 'hint' => 'Выключено — телефона нет нигде на сайте.', 'sanitize' => 'bc_contacts_sanitize_flag' ),
'email'      => array( 'label' => 'Email', 'type' => 'email', 'hint' => '', 'sanitize' => 'sanitize_email' ),
'telegram'   => array( 'label' => 'Telegram (ссылка)', 'type' => 'url', 'hint' => 'Полная ссылка: https://t.me/…', 'sanitize' => 'esc_url_raw' ),
'max'        => array( 'label' => 'MAX (ссылка)', 'type' => 'url', 'hint' => 'Полная ссылка: https://max.ru/…', 'sanitize' => 'esc_url_raw' ),
'vk'         => array( 'label' => 'ВКонтакте (ссылка)', 'type' => 'url', 'hint' => 'Полная ссылка: https://vk.com/…', 'sanitize' => 'esc_url_raw' ),
'address'    => array( 'label' => 'Адрес', 'type' => 'text', 'hint' => '', 'sanitize' => 'sanitize_text_field' ),
'ad_text'    => array( 'label' => 'Текст для рекламодателей', 'type' => 'textarea', 'hint' => 'Короткая строка под телефоном/почтой, например: «Реклама и сотрудничество».', 'sanitize' => 'sanitize_textarea_field' ),
);
}

/**
 * Чекбокс → '1' / ''.
 *
 * @param mixed $value Значение.
 * @return string
 */
function bc_contacts_sanitize_flag( $value ) {
return $value ? '1' : '';
}

/**
 * Регистрация опций (options_{name}).
 */
function bc_contacts_native_register() {
foreach ( bc_contacts_native_fields() as $key => $field ) {
register_setting(
'bc_contacts',
'options_' . $key,
array(
'type'              => 'string',
'sanitize_callback' => $field['sanitize'],
'default'           => '',
)
);
}
}

/**
 * Меню «Настройки сайта → Контакты».
 */
function bc_contacts_native_menu() {
add_menu_page( 'Настройки сайта', 'Настройки сайта', 'manage_options', 'bc-site-settings', 'bc_contacts_native_page', 'dashicons-admin-settings', 59 );
add_submenu_page( 'bc-site-settings', 'Контакты', 'Контакты', 'manage_options', 'bc-site-contacts', 'bc_contacts_native_page' );
}

/**
 * Форма контактов.
 */
function bc_contacts_native_page() {
if ( ! current_user_can( 'manage_options' ) ) {
return;
}
?>
<div class="wrap">
<h1>Контакты сайта</h1>
<p class="description">Выводятся в блоке «Контакты» на главной и в подвале. У организации — свои поля в её карточке.</p>
<form method="post" action="options.php">
<?php settings_fields( 'bc_contacts' ); ?>
<table class="form-table" role="presentation"><tbody>
<?php foreach ( bc_contacts_native_fields() as $key => $f ) : ?>
<?php $name = 'options_' . $key; $val = get_option( $name, '' ); ?>
<tr>
<th scope="row"><label for="<?php echo esc_attr( $name ); ?>"><?php echo esc_html( $f['label'] ); ?></label></th>
<td>
<?php if ( 'checkbox' === $f['type'] ) : ?>
<label><input type="checkbox" id="<?php echo esc_attr( $name ); ?>" name="<?php echo esc_attr( $name ); ?>" value="1" <?php checked( '1', $val ); ?>> включено</label>
<?php elseif ( 'textarea' === $f['type'] ) : ?>
<textarea id="<?php echo esc_attr( $name ); ?>" name="<?php echo esc_attr( $name ); ?>" rows="3" class="regular-text"><?php echo esc_textarea( (string) $val ); ?></textarea>
<?php else : ?>
<input type="<?php echo esc_attr( $f['type'] ); ?>" id="<?php echo esc_attr( $name ); ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( (string) $val ); ?>" class="regular-text">
<?php endif; ?>
<?php if ( ! empty( $f['hint'] ) ) : ?><p class="description"><?php echo esc_html( $f['hint'] ); ?></p><?php endif; ?>
</td>
</tr>
<?php endforeach; ?>
</tbody></table>
<?php submit_button( 'Сохранить' ); ?>
</form>
</div>
<?php
}