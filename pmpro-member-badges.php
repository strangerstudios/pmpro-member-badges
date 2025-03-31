<?php
/**
 * Plugin Name: Paid Memberships Pro - Member Badges
 * Plugin URI: https://www.paidmembershipspro.com/add-ons/member-badges/
 * Description: Assign unique member badges (images) to each membership level and display via a shortcode or template PHP function.
 * Version: 1.1
 * Author: Paid Memberships Pro
 * Author URI: https://www.paidmembershipspro.com
 * Text Domain: pmpro-member-badges
 * Domain Path: /languages
 * License: GPL-3.0
 */


/**
 * Load the languages folder for translations.
 */
function pmpromb_load_textdomain() {
	load_plugin_textdomain( 'pmpro-member-badges', false, basename( dirname( __FILE__ ) ) . '/languages' );
}
add_action( 'plugins_loaded', 'pmpromb_load_textdomain' );

function pmpromb_show_badge( $user_id = NULL, $echo = true, $args = array() ) {
	if ( empty( $user_id ) ) {
		global $current_user;
		$user_id = $current_user->ID;
	}

	if ( empty( $user_id ) ) {
		return false;
	}

	// Set default size if not set in args.
	if ( ! isset( $args['size'] ) ) {
		$args['size'] = 200;
	}

	$badges = array();

	if ( pmpro_hasMembershipLevel( NULL, $user_id ) ) {
		$levels = pmpro_getMembershipLevelsForUser( $user_id );
		foreach ( $levels as $level ) {
			$badges[] = array(
				'image' => pmpromb_getBadgeForLevel( $level->id ),
				'alt'   => $level->name . __( ' Member', 'pmpro-member-badges' ),
			);
		}
	} else {
		$non_member_badge = apply_filters( 'pmpromb_non_member_badge', '', $user_id );
		if ( ! empty( $non_member_badge ) ) {
			$badges[] = array(
				'image' => $non_member_badge,
				'alt'   => __( 'Non-member', 'pmpro-member-badges' ),
			);
		}
	}

	if ( empty( $badges ) ) {
		return;
	}

	$r = '';

	foreach ( $badges as $badge ) {
		$r .= '<img class="' . esc_attr( pmpro_get_element_class( 'pmpro_member_badge' ) ) . '" src="' . esc_url( $badge['image'] ) . '" border="0" alt="' . esc_attr( $badge['alt'] ) . '" width="' . esc_attr( $args['size'] ) . '" height="' . esc_attr( $args['size'] ) . '" />';
	}

	if ( $echo ) {
		echo wp_kses( $r, pmpromb_allowed_html() );
	}

	return $r;
}

/**
 * Shortcode to display a member badge in a page/post/widget: [pmpro_member_badges]
 */
function pmpromb_shortcode( $atts, $content = null, $code = '' ) {
	// Shortcode attributes.
	$image_align = isset( $atts['image_align'] ) ? $atts['image_align'] : null;
	$title       = isset( $atts['title'] ) ? $atts['title'] : null;
	$user_id     = isset( $atts['user_id'] ) ? intval( $atts['user_id'] ) : null;
	$size        = isset( $atts['size'] ) ? intval( $atts['size'] ) : 200;

	if ( empty( $user_id ) ) {
		global $current_user;
		$user_id = $current_user->ID;
	}

	// Normalize image alignment.
	if ( in_array( strtolower( $image_align ), array( '0', 'false', 'no' ), true ) ) {
		$image_align = false;
	}

	// Get badge markup.
	$badges = pmpromb_show_badge( $user_id, false, array( 'size' => $size ) );
	if ( empty( $badges ) ) {
		return '';
	}

	// Start output buffering.
	ob_start();

	if ( ! empty( $title ) ) {
		?>
		<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro' ) ); ?>">
			<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card' ) ); ?>">
				<h2 class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_title pmpro_font-large' ) ); ?>">
					<?php echo esc_html( $title ); ?>
				</h2>
				<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_content' ) ); ?>">
		<?php
	}

	$element_classes = array( 'pmpro_member_badges' );
	if ( ! empty( $image_align ) && $image_align !== 'none' ) {
		$element_classes[] = $image_align;
	}

	$element_class = implode( ' ', array_unique( $element_classes ) );
	?>
	<div class="<?php echo esc_attr( pmpro_get_element_class( $element_class ) ); ?>">
		<?php echo wp_kses( $badges, pmpromb_allowed_html() ); ?>
	</div> <!-- .pmpro_member_badges -->

	<?php if ( ! empty( $title ) ) { ?>
				</div> <!-- .pmpro_card_content -->
			</div> <!-- .pmpro_card -->
		</div> <!-- .pmpro -->
	<?php }

	return ob_get_clean();
}
add_shortcode( 'pmpro_member_badge', 'pmpromb_shortcode' );
add_shortcode( 'pmpro_member_badges', 'pmpromb_shortcode' ); // fallback for typo

/**
 * Get the badge image URL for a given membership level.
 *
 * @param int|null $level_id Membership level ID. If null, attempts to get current user's level.
 * @return string URL of the badge image.
 */
function pmpromb_getBadgeForLevel( $level_id = null ) {
	// Use current user's level if none is provided.
	if ( empty( $level_id ) ) {
		if ( function_exists( 'pmpro_getMembershipLevelForUser' ) ) {
			$current_user = wp_get_current_user();
			$level = pmpro_getMembershipLevelForUser( $current_user->ID );
			if ( ! empty( $level ) && ! empty( $level->id ) ) {
				$level_id = $level->id;
			}
		}
	}

	// Get custom badge URL for this level if set.
	$url = null;
	if ( ! empty( $level_id ) ) {
		$url = get_option( 'pmpro_member_badge_' . $level_id );
	}

	// Fallback to default if no URL set.
	if ( empty( $url ) ) {
		$url = plugins_url( 'images/member.png', __FILE__ );
	}

	return $url;
}

/**
 * Function for allowed HTML tags in various templates
 *
 * @since 1.0
 * @return array $allowed_html The allowed HTML to be used for wp_kses escaping.
 */
function pmpromb_allowed_html() {
	$allowed_html = array(
		'img' => array(
			'src'    => array(),
			'alt'    => array(),
			'border' => array(),
			'class'  => array(),
			'width'  => array(),
			'height' => array(),
		),
	);

	/**
	 * Filters the allowed HTML tags for the Member Badges Add On
	 *
	 * @since TBD
	 * @param array $allowed_html The allowed html elements for the Member Badges escaping where wp_kses is used
	 */
	return apply_filters( 'pmpromb_allowed_html', $allowed_html );
}

/**
 * Settings
 */
function pmpromb_pmpro_membership_level_after_other_settings() {
?>
<style type="text/css">
	.member-badge-preview {
		display: block;
		height: auto;
		width: 150px;
	}
</style>
<table>
	<tbody class="form-table">
		<tr>
			<th scope="row" valign="top"><label for="member_badge"><?php esc_html_e( 'Member Badge', 'pmpro-member-badges' );?></label></th>
			<td>
				<?php
					$level_id = intval($_REQUEST['edit']);
					$member_badge_url = pmpromb_getBadgeForLevel($level_id);
				?>
				<img id="member_badge_preview" class="member-badge-preview" src="<?php echo esc_url($member_badge_url);?>">
				<input type="text" name="member_badge" id="member_badge" value="<?php echo esc_url($member_badge_url);?>" class="regular-text" />
				<input type='button' class="button-primary" value="<?php esc_html_e('Upload/Choose Image', 'pmpro-member-badges');?>" id="upload_member_badge"/><br />
				<p class="description"><?php esc_html_e('Enter the URL to the image above or use the Upload/Choose button to upload or choose an image from your media library.', 'pmpro-member-badges'); ?></p>
				<div id="member_badge_notice" class="notice notice-warning inline" style="display: none;">
					<p><?php esc_html_e('Click "Save Level" below to save this change.', 'pmpro-member-badges');?></p>
				</div>
			</td>
		</tr>
	</tbody>
</table>
<script type="text/javascript">
	jQuery(function($){
	/*
	 * Select/Upload image(s) event
	 * Reference from https://rudrastyh.com/wordpress/customizable-media-uploader.html
	 */
	jQuery('body').on('click', '#upload_member_badge', function(e){
		e.preventDefault();
 
    		var button = $(this),
    		
    		custom_uploader = wp.media({
			title: 'Insert image',
			library : {
				type : 'image'
			},
			button: {
				text: 'Use this image' // button label text
			},
			multiple: false // for multiple image selection set to true
		}).on('select', function() { // once image is selected.
			var attachment = custom_uploader.state().get('selection').first().toJSON();

			jQuery('#member_badge_preview').attr( 'src', attachment.url );
			jQuery('#member_badge').val( attachment.url );
			jQuery( '#member_badge_notice' ).show();
		})
		.open();
	}); 
});
</script>
<?php
}
add_action( 'pmpro_membership_level_after_other_settings', 'pmpromb_pmpro_membership_level_after_other_settings' );

/**
 * Save the member badge.
 */
function pmpromb_pmpro_save_membership_level( $level_id ) {
	if ( isset( $_REQUEST['member_badge'] ) ) {
		update_option( 'pmpro_member_badge_' . $level_id, esc_url_raw( $_REQUEST['member_badge'] ) );
	}
}
add_action( 'pmpro_save_membership_level', 'pmpromb_pmpro_save_membership_level' );

/**
 * Function to add links to the plugin row meta
 */
function pmpromb_plugin_row_meta( $links, $file ) {
	if ( strpos( $file, 'pmpro-member-badges.php' ) !== false ) {
		$new_links = array(
			'<a href="' . esc_url( 'https://www.paidmembershipspro.com/add-ons/member-badges/' ) . '" title="' . esc_attr( __( 'View Documentation', 'pmpro-member-badges' ) ) . '">' . __( 'Docs', 'pmpro-member-badges' ) . '</a>',
			'<a href="' . esc_url( 'https://www.paidmembershipspro.com/support/' ) . '" title="' . esc_attr( __( 'Visit Customer Support Forum', 'pmpro-member-badges' ) ) . '">' . __( 'Support', 'pmpro-member-badges' ) . '</a>',
		);
		$links     = array_merge( $links, $new_links );
	}
	return $links;
}
add_filter( 'plugin_row_meta', 'pmpromb_plugin_row_meta', 10, 2 );
