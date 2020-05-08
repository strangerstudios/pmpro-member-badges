<?php
/*
Plugin Name: Paid Memberships Pro - Member Badges Add On
Plugin URI: https://www.paidmembershipspro.com/add-ons/member-badges/
Description: Assign unique member badges (images) to each membership level and display via a shortcode or template PHP function.
Version: 1.0
Author: Paid Memberships Pro
Author URI: https://www.paidmembershipspro.com
*/

/*
	To display a member badge in a PHP file, set the $user_id var and add the code:
		if(function_exists("pmpromb_show_badge")) pmpromb_show_badge($user_id);

	You can add custom style using the class: img.pmpro_member_badges.
*/
function pmpromb_show_badge($user_id = NULL, $echo = true)
{
	if(empty($user_id))
	{
		global $current_user;
		$user_id = $current_user->ID;
	}

	if(empty($user_id))
		return false;

	if(pmpro_hasMembershipLevel(NULL, $user_id))
	{
		$badges = array();
		$levels = pmpro_getMembershipLevelsForUser($user_id);
		foreach($levels as $level) {
			$badges[] = array(
				'image' => pmpromb_getBadgeForLevel($level->id),
				'alt' => $alt = $level->name . __(" Member", "pmpromb")
			);
		}
	}
	else
	{
		$badges = apply_filters('pmpromb_non_member_badge', '', $user_id);
		$alt = __("Non-member", "pmpromb");
	}

	$r = '';

	foreach($badges as $badge) {
		$r .= '<img class="pmpro_member_badge" src="' . esc_url($badge['image']) . '" border="0" alt="' . $badge['alt'] . '" />';
	}

	if($echo)
		echo $r;

	return $r;
}

/*
	Shortcode to display a member badge in a page/post/widget: [pmpro_member_badges]
*/
function pmpromb_shortcode($atts, $content=null, $code="")
{
	// $atts    ::= array of attributes
	// $content ::= text within enclosing form of shortcode element
	// $code    ::= the shortcode found, when == callback name
	// examples: [pmpro_member_badges image_align="none" title="My Member Badges"]

	extract(shortcode_atts(array(
		'image_align' => NULL,
		'title' => NULL,
	), $atts));

	if(empty($user_id))
	{
		global $current_user;
		$user_id = $current_user->ID;
	}

	if($image_align === "0" || $image_align === "false" || $image_align === "no")
		$image_align = false;
	else
		$image_align = $image_align;

	ob_start();
	?>
		<?php if(!empty($title)) { ?>
			<div id="pmpro_member_badges" class="pmpro_box">
				<h3><?php echo $title; ?></h3>
		<?php } ?>
			<div class="pmpro_member_badge<?php if(!empty($image_align)) echo ' ' . $image_align; ?>">
				<?php pmpromb_show_badge($user_id); ?>
			</div>
		<?php if(!empty($title)) { ?>
			</div> <!-- end pmpro_member_badges -->
		<?php } ?>
	<?php
	$temp_content = ob_get_contents();
	ob_end_clean();
	return $temp_content;
}
add_shortcode('pmpro_member_badge','pmpromb_shortcode');
add_shortcode('pmpro_member_badges','pmpromb_shortcode');	//in case typo

/*
	Function to get a badge URL for level
*/
function pmpromb_getBadgeForLevel($level_id = NULL) {
	if(empty($level_id) && function_exists('pmpro_getMembershipLevelForUser')) {
		global $current_user;
		$level = pmpro_getMembershipLevelForUser($current_user->ID);
		if(!empty($level))
			$level_id = $level->id;
	}

	//default
	$default = plugins_url("images/member.jpg", __FILE__);

	//look up by level
	if(!empty($level_id)) {
		$url = get_option('pmpro_member_badge_' . $level_id, $default);
	}

	//use default if level badge is empty
	if(empty($url))
		$url = $default;

	return $url;
}

/*
	Settings
*/
function pmpromb_pmpro_membership_level_after_other_settings()
{
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
		<td>
			<tr>
				<th scope="row" valign="top"><label for="member_badge"><?php _e('Member Badge', 'pmpromb');?>:</label></th>
				<td>
					<?php
						$level_id = intval($_REQUEST['edit']);
						$member_badge_url = pmpromb_getBadgeForLevel($level_id);
					?>
					<img id="member_badge_preview" class="member-badge-preview" src="<?php echo esc_url($member_badge_url);?>">
					<input type="text" name="member_badge" id="member_badge" value="<?php echo esc_url($member_badge_url);?>" class="regular-text" />
					<input type='button' class="button-primary" value="<?php _e('Upload/Choose Image', 'pmpromb');?>" id="upload_member_badge"/><br />
					<p><small><?php _e('Enter the URL to the image above or use the Upload/Choose button to upload or choose an image from your media library.', 'pmpromb');?></small></p>
					<div id="member_badge_notice" class="notice notice-warning inline" style="display: none;">
						<p><?php _e('Click "Save Level" below to save this change.', 'pmpromb');?></p>
					</div>

				</td>
			</tr>
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
add_action("pmpro_membership_level_after_other_settings", "pmpromb_pmpro_membership_level_after_other_settings");

/*
	Save the member badge.
*/
function pmpromb_pmpro_save_membership_level($level_id)
{
	if(isset($_REQUEST['member_badge']))
		update_option('pmpro_member_badge_' . $level_id, $_REQUEST['member_badge']);
}
add_action("pmpro_save_membership_level", "pmpromb_pmpro_save_membership_level");

/*
	Function to add links to the plugin row meta
*/
function pmpromb_plugin_row_meta($links, $file) {
	if(strpos($file, 'pmpro-member-badges.php') !== false)
	{
		$new_links = array(
			'<a href="' . esc_url('https://www.paidmembershipspro.com/add-ons/member-badges/')  . '" title="' . esc_attr( __( 'View Documentation', 'pmpro' ) ) . '">' . __( 'Docs', 'pmpro' ) . '</a>',
			'<a href="' . esc_url('https://www.paidmembershipspro.com/support/') . '" title="' . esc_attr( __( 'Visit Customer Support Forum', 'pmpro' ) ) . '">' . __( 'Support', 'pmpro' ) . '</a>',
		);
		$links = array_merge($links, $new_links);
	}
	return $links;
}
add_filter('plugin_row_meta', 'pmpromb_plugin_row_meta', 10, 2);
