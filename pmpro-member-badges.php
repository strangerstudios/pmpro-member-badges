<?php
/*
Plugin Name: PMPro Member Badges
Plugin URI: http://www.paidmembershipspro.com/pmpro-member-badges/
Description: Helper plugin for adding member icon/badges next to username for members.
Version: .1
Author: Stranger Studios
Author URI: http://www.strangerstudios.com
*/

/*
	This is it! One function. Just add code like this anywhere you want to show a badge. Set the $user_id var.
	
	if(function_exists("pmpromb_show_badge")) pmpromb_show_badge($user_id);
	
	The code below will show a gold icon for members and a silver icon for non-members. You should adjust this as you.
	For example, you can show no icon for non-members. A specific icon for specific levels. Change the icons, etc.
	
	You'll also want to apply styles to img.pmpro_member_badges to style the icons how you want.
*/
function pmpromb_show_badge($user_id, $echo = true)
{
	if(pmpro_hasMembershipLevel())
	{
		$image = plugins_url("images/member_gold.png", __FILE__);
		$alt = "member";
	}
	else
	{
		$image = plugins_url("images/member_silver.png", __FILE__);
		$alt = "non-member";
	}
	
	$r = '<img class="pmpro_member_badge" src="' . $image . '" border="0" alt="' . $alt . '" />';
	
	if($echo)
		echo $r;
		
	return $r;
}