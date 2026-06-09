<?php
/**
 * Example: Using ACF Options as Submenu
 *
 * This file demonstrates how to register ACF Options pages as submenus
 * under existing WordPress admin menus.
 *
 * @package CodeSoup\Options
 */

// Example 1: Add as submenu under Settings.
$settings_manager = \CodeSoup\Options\Manager::create(
	'site_settings',
	array(
		'menu' => array(
			'label'  => 'Site Settings',
			'parent' => 'options-general.php',
		),
	)
);

$settings_manager->register_page(
	array(
		'id'          => 'general',
		'title'       => 'General Settings',
		'capability'  => 'manage_options',
		'description' => 'General site settings',
	)
);

$settings_manager->init();

// Example 2: Add as submenu under Tools.
$tools_manager = \CodeSoup\Options\Manager::create(
	'site_tools',
	array(
		'menu' => array(
			'label'  => 'Site Tools',
			'parent' => 'tools.php',
		),
	)
);

$tools_manager->register_page(
	array(
		'id'          => 'maintenance',
		'title'       => 'Maintenance Mode',
		'capability'  => 'manage_options',
		'description' => 'Maintenance mode configuration',
	)
);

$tools_manager->init();

// Example 3: Add as submenu under Appearance.
$theme_manager = \CodeSoup\Options\Manager::create(
	'theme_options',
	array(
		'menu' => array(
			'label'  => 'Theme Options',
			'parent' => 'themes.php',
		),
	)
);

$theme_manager->register_pages(
	array(
		array(
			'id'          => 'header',
			'title'       => 'Header Settings',
			'capability'  => 'edit_theme_options',
			'description' => 'Header configuration and styling',
		),
		array(
			'id'          => 'footer',
			'title'       => 'Footer Settings',
			'capability'  => 'edit_theme_options',
			'description' => 'Footer configuration and styling',
		),
	)
);

$theme_manager->init();

// Example 4: Top-level menu (default behavior).
$main_manager = \CodeSoup\Options\Manager::create(
	'main_options',
	array(
		'menu' => array(
			'label'    => 'Main Options',
			'icon'     => 'dashicons-admin-settings',
			'position' => 50,
		),
	)
);

$main_manager->register_page(
	array(
		'id'          => 'general',
		'title'       => 'General',
		'capability'  => 'manage_options',
		'description' => 'General options',
	)
);

$main_manager->init();
