<?php
/**
 * Actions Metabox Template
 *
 * @package CodeSoup\Options
 *
 * @var \WP_Post                     $post Post object.
 * @var array                        $args Metabox arguments.
 * @var \CodeSoup\Options\Manager    $args['manager'] Manager instance.
 */

// Don't allow direct access to file.
defined( 'ABSPATH' ) || die; ?>

<div class="submitbox" id="submitpost">
	<div id="publishing-action">
		<span class="spinner"></span>
		<?php
		submit_button(
			__( 'Update', 'codesoup-options' ),
			'primary large',
			'publish',
			false
		);
		?>
	</div>
	<div class="clear"></div>
</div>

