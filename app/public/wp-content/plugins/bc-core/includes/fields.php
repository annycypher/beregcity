<?php
/**
 * Поля организации — ACF local field group (Этап 3.1b).
 *
 * Ключи field_bc_*, labels по-русски. Схема — по карте данных ПРОТОКОЛа.
 * Галерея — пустое поле (контент позже); erid — Этап 4/5; статистика — Этап 3а.
 *
 * @package BC_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Регистрация группы полей.
 */
function bc_acf_org_fields() {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	acf_add_local_field_group(
		array(
			'key'                   => 'group_bc_org',
			'title'                 => 'Поля организации',
			'fields'                => array(
				array(
					'key'   => 'field_bc_phone',
					'label' => 'Телефон',
					'name'  => 'bc_phone',
					'type'  => 'text',
				),
				array(
					'key'       => 'field_bc_excerpt',
					'label'     => 'Краткое описание',
					'name'      => 'bc_excerpt',
					'type'      => 'textarea',
					'maxlength' => 300,
					'rows'      => 3,
				),
				array(
					'key'   => 'field_bc_address',
					'label' => 'Адрес',
					'name'  => 'bc_address',
					'type'  => 'text',
				),
				array(
					'key'   => 'field_bc_lat',
					'label' => 'Широта (lat)',
					'name'  => 'bc_lat',
					'type'  => 'text',
				),
				array(
					'key'   => 'field_bc_lng',
					'label' => 'Долгота (lng)',
					'name'  => 'bc_lng',
					'type'  => 'text',
				),
				array(
					'key'          => 'field_bc_schedule',
					'label'        => 'График работы',
					'name'         => 'bc_schedule',
					'type'         => 'repeater',
					'layout'       => 'table',
					'button_label' => 'Добавить день',
					'sub_fields'   => array(
						array(
							'key'     => 'field_bc_schedule_day',
							'label'   => 'День недели',
							'name'    => 'day',
							'type'    => 'select',
							'choices' => array(
								1 => 'Понедельник',
								2 => 'Вторник',
								3 => 'Среда',
								4 => 'Четверг',
								5 => 'Пятница',
								6 => 'Суббота',
								7 => 'Воскресенье',
							),
						),
						array(
							'key'   => 'field_bc_schedule_from',
							'label' => 'Открытие',
							'name'  => 'time_from',
							'type'  => 'time_picker',
						),
						array(
							'key'   => 'field_bc_schedule_to',
							'label' => 'Закрытие',
							'name'  => 'time_to',
							'type'  => 'time_picker',
						),
					),
				),
				array(
					'key'          => 'field_bc_exceptions',
					'label'        => 'Особые дни графика',
					'name'         => 'bc_exceptions',
					'type'         => 'repeater',
					'layout'       => 'table',
					'button_label' => 'Добавить особый день',
					'sub_fields'   => array(
						array(
							'key'           => 'field_bc_exception_date',
							'label'         => 'Дата',
							'name'          => 'date',
							'type'          => 'date_picker',
							'display_format' => 'd.m.Y',
							'return_format'  => 'Y-m-d',
						),
						array(
							'key'   => 'field_bc_exception_note',
							'label' => 'Текст (часы/примечание)',
							'name'  => 'note',
							'type'  => 'text',
						),
					),
				),
				array(
					'key'   => 'field_bc_website',
					'label' => 'Сайт',
					'name'  => 'bc_website',
					'type'  => 'url',
				),
				array(
					'key'   => 'field_bc_whatsapp',
					'label' => 'WhatsApp',
					'name'  => 'bc_whatsapp',
					'type'  => 'text',
				),
				array(
					'key'   => 'field_bc_telegram',
					'label' => 'Telegram',
					'name'  => 'bc_telegram',
					'type'  => 'text',
				),
				array(
					'key'   => 'field_bc_vk',
					'label' => 'ВКонтакте',
					'name'  => 'bc_vk',
					'type'  => 'text',
				),
				array(
					'key'           => 'field_bc_plan',
					'label'         => 'Тариф',
					'name'          => 'bc_plan',
					'type'          => 'select',
					'choices'       => array(
						'trial'   => 'Триал',
						'free'    => 'Free',
						'standard' => 'Стандарт',
						'premium' => 'Премиум',
					),
					'default_value' => 'free',
				),
				array(
					'key'           => 'field_bc_plan_until',
					'label'         => 'Тариф до',
					'name'          => 'bc_plan_until',
					'type'          => 'date_picker',
					'display_format' => 'd.m.Y',
					'return_format'  => 'Y-m-d',
				),
				array(
					'key'           => 'field_bc_trial_until',
					'label'         => 'Триал до',
					'name'          => 'bc_trial_until',
					'type'          => 'date_picker',
					'display_format' => 'd.m.Y',
					'return_format'  => 'Y-m-d',
				),
				array(
					'key'   => 'field_bc_is_verified',
					'label' => 'Проверено',
					'name'  => 'bc_is_verified',
					'type'  => 'true_false',
					'ui'    => 1,
				),
				array(
					'key'   => 'field_bc_keywords',
					'label' => 'Ключевые слова',
					'name'  => 'bc_keywords',
					'type'  => 'text',
				),
				array(
					'key'   => 'field_bc_gallery',
					'label' => 'Галерея',
					'name'  => 'bc_gallery',
					'type'  => 'gallery',
				),
			),
			'location'              => array(
				array(
					array(
						'param'    => 'post_type',
						'operator' => '==',
						'value'    => 'organizations',
					),
				),
			),
			'menu_order'            => 0,
			'position'              => 'normal',
			'style'                 => 'default',
			'label_placement'       => 'top',
			'instruction_placement' => 'label',
		)
	);
}
add_action( 'acf/init', 'bc_acf_org_fields' );
