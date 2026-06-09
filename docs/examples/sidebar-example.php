<?php
/**
 * Example Sidebar Template
 *
 * This is an example sidebar template showing how to create custom sidebar content.
 * Copy this file to your theme or plugin and customize as needed.
 *
 * @package CodeSoup\Options
 *
 * Available variables:
 * @var \CodeSoup\Options\Admin_Page $this Admin_Page instance
 */

defined( 'ABSPATH' ) || die;
?>

<div style="padding: 20px; background: #f0f0f0; border-radius: 4px; text-align: center;">
	<h3 style="margin: 0 0 10px 0; font-size: 14px;">Custom Sidebar</h3>
	<p style="margin: 0; font-size: 12px; color: #666;">
		This is a 200px wide sidebar area.
	</p>
	<p style="margin: 10px 0 0 0; font-size: 12px; color: #666;">
		Add your banners, links, or custom content here.
	</p>
</div>

<!-- Example: Banner -->
<div style="margin-top: 16px; background: #fff; border: 1px solid rgba(0,0,0,0.05); border-radius: 4px; overflow: hidden;">
	<a href="https://example.com" target="_blank" style="display: block;">
		<img src="https://via.placeholder.com/200x200/2271b1/ffffff?text=Your+Ad+Here" 
		     alt="Advertisement" 
		     style="display: block; width: 100%; height: auto;" />
	</a>
</div>

<!-- Example: Quick Links -->
<div style="margin-top: 16px; background: #fff; border: 1px solid rgba(0,0,0,0.05); border-radius: 4px; padding: 16px;">
	<h4 style="margin: 0 0 12px 0; font-size: 13px; font-weight: 600;">Quick Links</h4>
	<ul style="margin: 0; padding: 0; list-style: none;">
		<li style="margin-bottom: 8px;">
			<a href="#" style="font-size: 12px; text-decoration: none; color: #2271b1;">Documentation</a>
		</li>
		<li style="margin-bottom: 8px;">
			<a href="#" style="font-size: 12px; text-decoration: none; color: #2271b1;">Support</a>
		</li>
		<li style="margin-bottom: 0;">
			<a href="#" style="font-size: 12px; text-decoration: none; color: #2271b1;">Rate Plugin</a>
		</li>
	</ul>
</div>
