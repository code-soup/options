<?php
/**
 * Example: Tabbed UI Mode
 *
 * Demonstrates using the tabbed UI interface instead of the default WordPress pages list.
 *
 * @package CodeSoup\Options
 */

use CodeSoup\Options\Manager;

add_action(
	'plugins_loaded',
	function () {
		$manager = Manager::create(
			'site_settings',
			array(
				'menu_label'   => 'Site Settings',
				'ui_mode'      => 'tabs',
				'tab_position' => 'left',
				'integrations' => array(
					'acf' => array( 'enabled' => false ),
				),
			)
		);

		$manager->register_pages(
			array(
				array(
					'id'          => 'general',
					'title'       => 'General Settings',
					'capability'  => 'manage_options',
					'description' => 'General site configuration and information',
				),
				array(
					'id'          => 'footer',
					'title'       => 'Footer Settings',
					'capability'  => 'manage_options',
					'description' => 'Footer content and scripts',
				),
				array(
					'id'          => 'advanced',
					'title'       => 'Advanced Settings',
					'capability'  => 'manage_options',
					'description' => 'Advanced configuration options',
				),
			)
		);

		$manager->register_metabox(
			array(
				'page'  => 'general',
				'title' => 'Site Information',
				'path'  => __DIR__ . '/templates/site-info-metabox.php',
			)
		);

		$manager->register_metabox(
			array(
				'page'  => 'footer',
				'title' => 'Footer Content',
				'path'  => __DIR__ . '/templates/footer-metabox.php',
			)
		);

		$manager->register_metabox(
			array(
				'page'     => 'advanced',
				'title'    => 'Advanced Options',
				'path'     => __DIR__ . '/templates/advanced-metabox.php',
				'context'  => 'normal',
				'priority' => 'high',
			)
		);

		$manager->init();

		add_action(
			'save_post',
			function ( $post_id ) use ( $manager ) {
				if ( ! isset( $_POST['_wpnonce'] ) ) {
					return;
				}

				$nonce = sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) );

				if ( isset( $_POST['site_info'] ) ) {
					$data = array(
						'site_name'        => sanitize_text_field( $_POST['site_info']['site_name'] ?? '' ),
						'site_description' => sanitize_textarea_field( $_POST['site_info']['site_description'] ?? '' ),
						'contact_email'    => sanitize_email( $_POST['site_info']['contact_email'] ?? '' ),
					);

					$manager->save_options(
						array(
							'post_id' => $post_id,
							'nonce'   => $nonce,
							'data'    => $data,
						)
					);
				}

				if ( isset( $_POST['footer_content'] ) ) {
					$data = array(
						'footer_text'   => wp_kses_post( $_POST['footer_content']['footer_text'] ?? '' ),
						'footer_script' => sanitize_textarea_field( $_POST['footer_content']['footer_script'] ?? '' ),
					);

					$manager->save_options(
						array(
							'post_id' => $post_id,
							'nonce'   => $nonce,
							'data'    => $data,
						)
					);
				}

				if ( isset( $_POST['advanced_options'] ) ) {
					$data = array(
						'enable_feature_x' => isset( $_POST['advanced_options']['enable_feature_x'] ),
						'api_key'          => sanitize_text_field( $_POST['advanced_options']['api_key'] ?? '' ),
					);

					$manager->save_options(
						array(
							'post_id' => $post_id,
							'nonce'   => $nonce,
							'data'    => $data,
						)
					);
				}
			}
		);
	}
);

