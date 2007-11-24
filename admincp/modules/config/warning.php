<?php
/**
 * MyBB 1.2
 * Copyright � 2007 MyBB Group, All Rights Reserved
 *
 * Website: http://www.mybboard.net
 * License: http://www.mybboard.net/about/license
 *
 * $Id$
 */

// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

require_once MYBB_ROOT."inc/functions_warnings.php";

$page->add_breadcrumb_item($lang->warning_system, "index.php?".SID."&amp;module=config/warning");

if($mybb->input['action'] == "levels" || $mybb->input['action'] == "add_type" || $mybb->input['action'] == "add_level" || !$mybb->input['action'])
{
	$sub_tabs['manage_types'] = array(
		'title' => $lang->warning_types,
		'link' => "index.php?".SID."&amp;module=config/warning",
		'description' => $lang->warning_types_desc
	);
	$sub_tabs['add_type'] = array(
		'title'=> $lang->add_warning_type,
		'link' => "index.php?".SID."&amp;module=config/warning&amp;action=add_type",
		'description' => $lang->add_warning_type_desc
	);
	$sub_tabs['manage_levels'] = array(
		'title' => $lang->warning_levels,
		'link' => "index.php?".SID."&amp;module=config/warning&amp;action=levels",
		'description' => $lang->warning_levels_desc,
	);
	$sub_tabs['add_level'] = array(
		'title'=> $lang->add_warning_level,
		'link' => "index.php?".SID."&amp;module=config/warning&amp;action=add_level",
		'description' => $lang->add_warning_level_desc
	);
}

if($mybb->input['action'] == "add_level")
{
	if($mybb->request_method == "post")
	{
		if(!is_numeric($mybb->input['percentage']) || $mybb->input['percentage'] > 100 || $mybb->input['percentage'] < 0)
		{
			$errors[] = $lang->error_invalid_warning_percentage;
		}

		if(!$errors)
		{
			// Ban
			if($mybb->input['action_type'] == 1)
			{
				$action = array(
					"type" => 1,
					"usergroup" => intval($mybb->input['action_1_usergroup']),
					"length" => fetch_time_length($mybb->input['action_1_time'], $mybb->input['action_1_period'])
				);
			}
			// Suspend posting
			else if($mybb->input['action_type'] == 2)
			{
				$action = array(
					"type" => 2,
					"length" => fetch_time_length($mybb->input['action_2_time'], $mybb->input['action_2_period'])
				);
			}
			// Moderate posts
			else if($mybb->input['action_type'] == 3)
			{
				$action = array(
					"type" => 3,
					"length" => fetch_time_length($mybb->input['action_3_time'], $mybb->input['action_3_period'])
				);
			}
			$new_level = array(
				"percentage" => intval($mybb->input['percentage']),
				"action" => serialize($action)
			);
			
			$lid = $db->insert_query("warninglevels", $new_level);

			// Log admin action
			log_admin_action($lid, $mybb->input['percentage']);
			
			flash_message($lang->success_warning_level_created, 'success');
			admin_redirect("index.php?".SID."&module=config/warning&action=levels");
		}
	}
	
	$page->add_breadcrumb_item($lang->add_warning_level);
	$page->output_header($lang->warning_levels." - ".$lang->add_warning_level);
	
	$page->output_nav_tabs($sub_tabs, 'add_level');
	$form = new Form("index.php?".SID."&amp;module=config/warning&amp;action=add_level", "post");
	
	
	if($errors)
	{
		$page->output_inline_error($errors);
		$action_checked[$mybb->input['action_type']] = "checked=\"checked\"";
	}

	$form_container = new FormContainer($lang->add_warning_level);
	$form_container->output_row($lang->warning_points_percentage, $lang->warning_points_percentage_desc, $form->generate_text_box('percentage', $mybb->input['percentage'], array('id' => 'percentage')), 'percentage');

	$query = $db->simple_select("usergroups", "*", "isbannedgroup=1");
	while($group = $db->fetch_array($query))
	{
		$banned_groups[$group['gid']] = $group['title'];
	}
	
	$periods = array(
		"hours" => $lang->expiration_hours,
		"days" => $lang->expiration_days,
		"weeks" => $lang->expiration_weeks,
		"months" => $lang->expiration_months,
		"never" => $lang->expiration_never
	);

	$actions = "<script type=\"text/javascript\">
	function checkAction()
	{
		var checked = '';
		document.getElementsByClassName('actions_check').each(function(e)
		{
			if(e.checked == true)
			{
				checked = e.value;
			}
		});
		document.getElementsByClassName('actions').each(function(e)
		{
			Element.hide(e);
		});
		if($('action_'+checked))
		{
			Element.show('action_'+checked);
		}
	}	
	</script>
	<dl style=\"margin-top: 0; margin-bottom: 0; width: 100%;\">
		<dt><label style=\"display: block;\"><input type=\"radio\" name=\"action_type\" value=\"1\" {$action_checked[1]} class=\"actions_check\" onclick=\"checkAction();\" style=\"vertical-align: middle;\" /> <strong>{$lang->ban_user}</strong></label></dt>
		<dd style=\"margin-top: 4px;\" id=\"action_1\" class=\"actions\">
			<table cellpadding=\"4\">
				<tr>
					<td class=\"smalltext\">{$lang->banned_group}</td>
					<td>".$form->generate_select_box('action_1_usergroup', $banned_groups, $mybb->input['action_1_usergroup'])."</td>
				</tr>
				<tr>
					<td class=\"smalltext\">{$lang->ban_length}</td>
					<td>".$form->generate_text_box('action_1_time', $mybb->input['action_1_time'], array('style' => 'width: 2em;'))." ".$form->generate_select_box('action_1_period', $periods, $mybb->input['action_1_period'])."</td>
				</tr>
			</table>
		</dd>
		<dt><label style=\"display: block;\"><input type=\"radio\" name=\"action_type\" value=\"2\" {$action_checked[2]} class=\"actions_check\" onclick=\"checkAction();\" style=\"vertical-align: middle;\" /> <strong>{$lang->suspend_posting_privileges}</strong></label></dt>
		<dd style=\"margin-top: 4px;\" id=\"action_2\" class=\"actions\">
			<table cellpadding=\"4\">
				<tr>
					<td class=\"smalltext\">{$lang->suspension_length}</td>
					<td>".$form->generate_text_box('action_2_time', $mybb->input['action_2_time'], array('style' => 'width: 2em;'))." ".$form->generate_select_box('action_2_period', $periods, $mybb->input['action_2_period'])."</td>
				</tr>
			</table>
		</dd>
		<dt><label style=\"display: block;\"><input type=\"radio\" name=\"action_type\" value=\"3\" {$action_checked[3]} class=\"actions_check\" onclick=\"checkAction();\" style=\"vertical-align: middle;\" /> <strong>{$lang->moderate_posts}</strong></label></dt>
		<dd style=\"margin-top: 4px;\" id=\"action_3\" class=\"actions\">
			<table cellpadding=\"4\">
				<tr>
					<td class=\"smalltext\">{$lang->moderation_length}</td>
					<td>".$form->generate_text_box('action_3_time', $mybb->input['action_3_time'], array('style' => 'width: 2em;'))." ".$form->generate_select_box('action_3_period', $periods, $mybb->input['action_3_period'])."</td>
				</tr>
			</table>
		</dd>
	</dl>
	<script type=\"text/javascript\">
	checkAction();
	</script>";
	$form_container->output_row($lang->action_to_be_taken, $lang->action_to_be_taken_desc, $actions);
	$form_container->end();

	$buttons[] = $form->generate_submit_button($lang->save_warning_level);

	$form->output_submit_wrapper($buttons);
	$form->end();

	$page->output_footer();
}

if($mybb->input['action'] == "edit_level")
{
	$query = $db->simple_select("warninglevels", "*", "lid='".intval($mybb->input['lid'])."'");
	$level = $db->fetch_array($query);

	// Does the warning level not exist?
	if(!$level['lid'])
	{
		flash_message($lang->error_invalid_warning_level, 'error');
		admin_redirect("index.php?".SID."&module=config/warning");
	}

	if($mybb->request_method == "post")
	{
		if(!is_numeric($mybb->input['percentage']) || $mybb->input['percentage'] > 100 || $mybb->input['percentage'] < 0)
		{
			$errors[] = $lang->error_invalid_warning_percentage;
		}

		if(!$errors)
		{
			// Ban
			if($mybb->input['action_type'] == 1)
			{
				$action = array(
					"type" => 1,
					"usergroup" => intval($mybb->input['action_1_usergroup']),
					"length" => fetch_time_length($mybb->input['action_1_time'], $mybb->input['action_1_period'])
				);
			}
			// Suspend posting
			else if($mybb->input['action_type'] == 2)
			{
				$action = array(
					"type" => 2,
					"length" => fetch_time_length($mybb->input['action_2_time'], $mybb->input['action_2_period'])
				);
			}
			// Moderate posts
			else if($mybb->input['action_type'] == 3)
			{
				$action = array(
					"type" => 3,
					"length" => fetch_time_length($mybb->input['action_3_time'], $mybb->input['action_3_period'])
				);
			}
			$updated_level = array(
				"percentage" => intval($mybb->input['percentage']),
				"action" => serialize($action)
			);
			
			$db->update_query("warninglevels", $updated_level, "lid='{$level['lid']}'");

			// Log admin action
			log_admin_action($level['lid'], $mybb->input['percentage']);

			flash_message($lang->success_warning_level_updated, 'success');
			admin_redirect("index.php?".SID."&module=config/warning&action=levels");
		}
	}
	
	$page->add_breadcrumb_item($lang->edit_warning_level);
	$page->output_header($lang->warning_levels." - ".$lang->edit_warning_level);
	
	$sub_tabs['edit_level'] = array(
		'link' => "index.php?".SID."&amp;module=config/warning&amp;action=edit_level&amp;lid={$level['lid']}",
		'title' => $lang->edit_warning_level,
		'description' => $lang->edit_warning_level_desc
	);
	
	$page->output_nav_tabs($sub_tabs, 'edit_level');
	$form = new Form("index.php?".SID."&amp;module=config/warning&amp;action=edit_level&amp;lid={$level['lid']}", "post");
	
	if($errors)
	{
		$page->output_inline_error($errors);
	}
	else
	{
		$mybb->input = array(
			"percentage" => $level['percentage'],
		);
		$action = unserialize($level['action']);
		if($action['type'] == 1)
		{
			$mybb->input['action_1_usergroup'] = $action['usergroup'];
			$length = fetch_friendly_expiration($action['length']);
			$mybb->input['action_1_time'] = $length['time'];
			$mybb->input['action_1_period'] = $length['period'];
		}
		else if($action['type'] == 2)
		{
			$length = fetch_friendly_expiration($action['length']);
			$mybb->input['action_2_time'] = $length['time'];
			$mybb->input['action_2_period'] = $length['period'];
		}
		else if($action['type'] == 3)
		{
			$length = fetch_friendly_expiration($action['length']);
			$mybb->input['action_3_time'] = $length['time'];
			$mybb->input['action_3_period'] = $length['period'];
		}
		$action_checked[$action['type']] = "checked=\"checked\"";
	}

	$form_container = new FormContainer($lang->edit_warning_level);
	$form_container->output_row($lang->warning_points_percentage, $lang->warning_points_percentage_desc, $form->generate_text_box('percentage', $mybb->input['percentage'], array('id' => 'percentage')), 'percentage');

	$query = $db->simple_select("usergroups", "*", "isbannedgroup=1");
	while($group = $db->fetch_array($query))
	{
		$banned_groups[$group['gid']] = $group['title'];
	}
	
	$periods = array(
		"hours" => $lang->expiration_hours,
		"days" => $lang->expiration_days,
		"weeks" => $lang->expiration_weeks,
		"months" => $lang->expiration_months,
		"never" => $lang->expiration_never
	);

	$actions = "<script type=\"text/javascript\">
	function checkAction()
	{
		var checked = '';
		document.getElementsByClassName('actions_check').each(function(e)
		{
			if(e.checked == true)
			{
				checked = e.value;
			}
		});
		document.getElementsByClassName('actions').each(function(e)
		{
			Element.hide(e);
		});
		if($('action_'+checked))
		{
			Element.show('action_'+checked);
		}
	}	
	</script>
	<dl style=\"margin-top: 0; margin-bottom: 0; width: 100%;\">
		<dt><label style=\"display: block;\"><input type=\"radio\" name=\"action_type\" value=\"1\" {$action_checked[1]} class=\"actions_check\" onclick=\"checkAction();\" style=\"vertical-align: middle;\" /> <strong>{$lang->ban_user}</strong></label></dt>
		<dd style=\"margin-top: 4px;\" id=\"action_1\" class=\"actions\">
			<table cellpadding=\"4\">
				<tr>
					<td class=\"smalltext\">{$lang->banned_group}</td>
					<td>".$form->generate_select_box('action_1_usergroup', $banned_groups, $mybb->input['action_1_usergroup'])."</td>
				</tr>
				<tr>
					<td class=\"smalltext\">{$lang->ban_length}</td>
					<td>".$form->generate_text_box('action_1_time', $mybb->input['action_1_time'], array('style' => 'width: 2em;'))." ".$form->generate_select_box('action_1_period', $periods, $mybb->input['action_1_period'])."</td>
				</tr>
			</table>
		</dd>
		<dt><label style=\"display: block;\"><input type=\"radio\" name=\"action_type\" value=\"2\" {$action_checked[2]} class=\"actions_check\" onclick=\"checkAction();\" style=\"vertical-align: middle;\" /> <strong>{$lang->suspend_posting_privileges}</strong></label></dt>
		<dd style=\"margin-top: 4px;\" id=\"action_2\" class=\"actions\">
			<table cellpadding=\"4\">
				<tr>
					<td class=\"smalltext\">{$lang->suspension_length}</td>
					<td>".$form->generate_text_box('action_2_time', $mybb->input['action_2_time'], array('style' => 'width: 2em;'))." ".$form->generate_select_box('action_2_period', $periods, $mybb->input['action_2_period'])."</td>
				</tr>
			</table>
		</dd>
		<dt><label style=\"display: block;\"><input type=\"radio\" name=\"action_type\" value=\"3\" {$action_checked[3]} class=\"actions_check\" onclick=\"checkAction();\" style=\"vertical-align: middle;\" /> <strong>{$lang->moderate_posts}</strong></label></dt>
		<dd style=\"margin-top: 4px;\" id=\"action_3\" class=\"actions\">
			<table cellpadding=\"4\">
				<tr>
					<td class=\"smalltext\">{$lang->moderation_length}</td>
					<td>".$form->generate_text_box('action_3_time', $mybb->input['action_3_time'], array('style' => 'width: 2em;'))." ".$form->generate_select_box('action_3_period', $periods, $mybb->input['action_3_period'])."</td>
				</tr>
			</table>
		</dd>
	</dl>
	<script type=\"text/javascript\">
	checkAction();
	</script>";
	$form_container->output_row($lang->action_to_be_taken, $lang->action_to_be_taken_desc, $actions);
	$form_container->end();

	$buttons[] = $form->generate_submit_button($lang->save_warning_level);

	$form->output_submit_wrapper($buttons);
	$form->end();

	$page->output_footer();
}

if($mybb->input['action'] == "delete_level")
{
	$query = $db->simple_select("warninglevels", "*", "lid='".intval($mybb->input['lid'])."'");
	$level = $db->fetch_array($query);

	// Does the warning level not exist?
	if(!$level['lid'])
	{
		flash_message($lang->error_invalid_warning_level, 'error');
		admin_redirect("index.php?".SID."&module=config/warning");
	}

	// User clicked no
	if($mybb->input['no'])
	{
		admin_redirect("index.php?".SID."&module=config/warning");
	}

	if($mybb->request_method == "post")
	{
		// Delete the level
		$db->delete_query("warninglevels", "lid='{$level['lid']}'");

		// Log admin action
		log_admin_action($level['percentage']);

		flash_message($lang->success_warning_level_deleted, 'success');
		admin_redirect("index.php?".SID."&module=config/warning");
	}
	else
	{
		$page->output_confirm_action("index.php?".SID."&amp;module=config/warning&amp;action=delete_level&amp;lid={$level['lid']}", $lang->confirm_warning_level_deletion);
	}
}

if($mybb->input['action'] == "add_type")
{
	if($mybb->request_method == "post")
	{
		if(!trim($mybb->input['title']))
		{
			$errors[] = $lang->error_missing_type_title;
		}

		if(!is_numeric($mybb->input['points']) || $mybb->input['points'] > $mybb->settings['maxwarningpoints'] || $mybb->input['points'] < 0)
		{
			$errors[] = sprintf($lang->error_missing_type_points, $mybb->settings['maxwarningpoints']);
		}

		if(!$errors)
		{
			$new_type = array(
				"title" => $db->escape_string($mybb->input['title']),
				"points" => intval($mybb->input['points']),
				"expirationtime" =>  fetch_time_length($mybb->input['expire_time'], $mybb->input['expire_period'])
			);
			
			$tid = $db->insert_query("warningtypes", $new_type);

			// Log admin action
			log_admin_action($tid, $mybb->input['title']);
			
			flash_message($lang->success_warning_type_created, 'success');
			admin_redirect("index.php?".SID."&module=config/warning");
		}
	}
	else
	{
		$mybb->input = array(
			"points" => "2",
			"expire_time" => 1,
			"expire_period" => "days"
		);
	}
	
	$page->add_breadcrumb_item($lang->add_warning_type);
	$page->output_header($lang->warning_types." - ".$lang->add_warning_type);
	
	$page->output_nav_tabs($sub_tabs, 'add_type');
	$form = new Form("index.php?".SID."&amp;module=config/warning&amp;action=add_type", "post");
	
	
	if($errors)
	{
		$page->output_inline_error($errors);
	}

	$form_container = new FormContainer($lang->add_warning_type);
	$form_container->output_row($lang->title." <em>*</em>", "", $form->generate_text_box('title', $mybb->input['title'], array('id' => 'title')), 'title');
	$form_container->output_row($lang->points_to_add." <em>*</em>", $lang->points_to_add_desc, $form->generate_text_box('points', $mybb->input['points'], array('id' => 'points')), 'points');
	$expiration_periods = array(
		"hours" => $lang->expiration_hours,
		"days" => $lang->expiration_days,
		"weeks" => $lang->expiration_weeks,
		"months" => $lang->expiration_months,
		"never" => $lang->never
	);
	$form_container->output_row($lang->warning_expiry, $lang->warning_expiry_desc, $form->generate_text_box('expire_time', $mybb->input['expire_time'], array('id' => 'expire_time'))." ".$form->generate_select_box('expire_period', $expiration_periods, $mybb->input['expire_period'], array('id' => 'expire_period')), 'expire_time');
	$form_container->end();

	$buttons[] = $form->generate_submit_button($lang->save_warning_type);

	$form->output_submit_wrapper($buttons);
	$form->end();

	$page->output_footer();
}

if($mybb->input['action'] == "edit_type")
{
	$query = $db->simple_select("warningtypes", "*", "tid='".intval($mybb->input['tid'])."'");
	$type = $db->fetch_array($query);

	// Does the warning type not exist?
	if(!$type['tid'])
	{
		flash_message($lang->error_invalid_warning_type, 'error');
		admin_redirect("index.php?".SID."&module=config/warning");
	}

	if($mybb->request_method == "post")
	{
		if(!trim($mybb->input['title']))
		{
			$errors[] = $lang->error_missing_type_title;
		}

		if(!is_numeric($mybb->input['points']) || $mybb->input['points'] > $mybb->settings['maxwarningpoints'] || $mybb->input['points'] < 0)
		{
			$errors[] = sprintf($lang->error_missing_type_points, $mybb->settings['maxwarningpoints']);
		}

		if(!$errors)
		{
			$updated_type = array(
				"title" => $db->escape_string($mybb->input['title']),
				"points" => intval($mybb->input['points']),
				"expirationtime" =>  fetch_time_length($mybb->input['expire_time'], $mybb->input['expire_period'])
			);
			
			$db->update_query("warningtypes", $updated_type, "tid='{$type['tid']}'");

			// Log admin action
			log_admin_action($type['tid'], $mybb->input['title']);

			flash_message($lang->success_warning_type_updated, 'success');
			admin_redirect("index.php?".SID."&module=config/warning");
		}
	}
	else
	{
		$expiration = fetch_friendly_expiration($type['expirationtime']);
		$mybb->input = array(
			"title" => $type['title'],
			"points" => $type['points'],
			"expire_time" => $expiration['time'],
			"expire_period" => $expiration['period']
		);
	}
	
	$page->add_breadcrumb_item($lang->edit_warning_type);
	$page->output_header($lang->warning_types." - ".$lang->edit_warning_type);
	
	$sub_tabs['edit_type'] = array(
		'link' => "index.php?".SID."&amp;module=config/warning&amp;action=edit_type&amp;tid={$type['tid']}",
		'title' => $lang->edit_warning_type,
		'description' => $lang->edit_warning_type_desc
	);
	
	$page->output_nav_tabs($sub_tabs, 'edit_type');
	$form = new Form("index.php?".SID."&amp;module=config/warning&amp;action=edit_type&amp;tid={$type['tid']}", "post");
	
	
	if($errors)
	{
		$page->output_inline_error($errors);
	}

	$form_container = new FormContainer($lang->edit_warning_type);
	$form_container->output_row($lang->title." <em>*</em>", "", $form->generate_text_box('title', $mybb->input['title'], array('id' => 'title')), 'title');
	$form_container->output_row($lang->points_to_add." <em>*</em>", $lang->points_to_add_desc, $form->generate_text_box('points', $mybb->input['points'], array('id' => 'points')), 'points');
	$expiration_periods = array(
		"hours" => $lang->expiration_hours,
		"days" => $lang->expiration_days,
		"weeks" => $lang->expiration_weeks,
		"months" => $lang->expiration_months,
		"never" => $lang->expiration_never
	);
	$form_container->output_row($lang->warning_expiry, $lang->warning_expiry_desc, $form->generate_text_box('expire_time', $mybb->input['expire_time'], array('id' => 'expire_time'))." ".$form->generate_select_box('expire_period', $expiration_periods, $mybb->input['expire_period'], array('id' => 'expire_period')), 'expire_time');
	$form_container->end();

	$buttons[] = $form->generate_submit_button($lang->save_warning_type);

	$form->output_submit_wrapper($buttons);
	$form->end();

	$page->output_footer();
}

if($mybb->input['action'] == "delete_type")
{
	$query = $db->simple_select("warningtypes", "*", "tid='".intval($mybb->input['tid'])."'");
	$type = $db->fetch_array($query);

	// Does the warning type not exist?
	if(!$type['tid'])
	{
		flash_message($lang->error_invalid_warning_type, 'error');
		admin_redirect("index.php?".SID."&module=config/warning");
	}

	// User clicked no
	if($mybb->input['no'])
	{
		admin_redirect("index.php?".SID."&module=config/warning");
	}

	if($mybb->request_method == "post")
	{
		// Delete the type
		$db->delete_query("warningtypes", "tid='{$type['tid']}'");

		// Log admin action
		log_admin_action($type['title']);

		flash_message($lang->success_warning_type_deleted, 'success');
		admin_redirect("index.php?".SID."&module=config/warning");
	}
	else
	{
		$page->output_confirm_action("index.php?".SID."&amp;module=config/warning&amp;action=delete_type&amp;tid={$type['tid']}", $lang->confirm_warning_type_deletion);
	}
}

if($mybb->input['action'] == "levels")
{
	$page->output_header($lang->warning_levels);

	$page->output_nav_tabs($sub_tabs, 'manage_levels');

	$table = new Table;
	$table->construct_header($lang->percentage, array('width' => '5%', 'class' => 'align_center'));
	$table->construct_header($lang->action_to_take);
	$table->construct_header($lang->controls, array("class" => "align_center", "colspan" => 2));
	
	$query = $db->simple_select("warninglevels", "*", "", array('order_by' => 'percentage'));
	while($level = $db->fetch_array($query))
	{
		$table->construct_cell("<strong>{$level['percentage']}%</strong>", array("class" => "align_center"));
		$action = unserialize($level['action']);
		// Ban user
		if($action['type'] == 1)
		{
			$ban_length = fetch_friendly_expiration($action['length']);
			$lang_str = "expiration_".$ban_length['period'];
			$group_name = $groupscache[$action['usergroup']]['title'];
			$type = sprintf($lang->move_banned_group, $group_name, $ban_length['time'], $lang->$lang_str);
		}
		else if($action['type'] == 2)
		{
			$period = fetch_friendly_expiration($action['length']);
			$lang_str = "expiration_".$period['period'];
			$type = sprintf($lang->suspend_posting, $period['time'], $lang->$lang_str);
		}
		else if($action['type'] == 3)
		{
			$period = fetch_friendly_expiration($action['length']);
			$lang_str = "expiration_".$period['period'];
			$type = sprintf($lang->moderate_new_posts, $period['time'], $lang->$lang_str);
		}
		$table->construct_cell($type);
		$table->construct_cell("<a href=\"index.php?".SID."&amp;module=config/warning&amp;action=edit_level&amp;lid={$level['lid']}\">{$lang->edit}</a>", array("width" => 100, "class" => "align_center"));
		$table->construct_cell("<a href=\"index.php?".SID."&amp;module=config/warning&amp;action=delete_level&amp;lid={$level['lid']}\" onclick=\"return AdminCP.deleteConfirmation(this, '{$lang->confirm_warning_level_deletion}')\">{$lang->delete}</a>", array("width" => 100, "class" => "align_center"));
		$table->construct_row();
	}
	
	if($table->num_rows() == 0)
	{
		$table->construct_cell($lang->no_warning_levels, array('colspan' => 4));
		$table->construct_row();
		$no_results = true;
	}
	
	$table->output($lang->warning_types);

	$page->output_footer();
}

if(!$mybb->input['action'])
{
	$page->output_header($lang->warning_types);

	$page->output_nav_tabs($sub_tabs, 'manage_types');

	$table = new Table;
	$table->construct_header($lang->warning_type);
	$table->construct_header($lang->points, array('width' => '5%', 'class' => 'align_center'));
	$table->construct_header($lang->expires_after, array('width' => '25%', 'class' => 'align_center'));
	$table->construct_header($lang->controls, array("class" => "align_center", "colspan" => 2));
	
	$query = $db->simple_select("warningtypes", "*", "", array('order_by' => 'title'));
	while($type = $db->fetch_array($query))
	{
		$type['name'] = htmlspecialchars_uni($type['title']);
		$table->construct_cell("<a href=\"index.php?".SID."&amp;module=config/warning&amp;action=edit_type&amp;tid={$type['tid']}\"><strong>{$type['title']}</strong></a>");
		$table->construct_cell("{$type['points']}", array("class" => "align_center"));
		$expiration = fetch_friendly_expiration($type['expirationtime']);
		$lang_str = "expiration_".$expiration['period'];
		if($type['expirationtime'] > 0)
		{
			$table->construct_cell("{$expiration['time']} {$lang->$lang_str}", array("class" => "align_center"));
		}
		else
		{
			$table->construct_cell($lang->never, array("class" => "align_center"));
		}
		$table->construct_cell("<a href=\"index.php?".SID."&amp;module=config/warning&amp;action=edit_type&amp;tid={$type['tid']}\">{$lang->edit}</a>", array("width" => 100, "class" => "align_center"));
		$table->construct_cell("<a href=\"index.php?".SID."&amp;module=config/warning&amp;action=delete_type&amp;tid={$type['tid']}\" onclick=\"return AdminCP.deleteConfirmation(this, '{$lang->confirm_warning_type_deletion}')\">{$lang->delete}</a>", array("width" => 100, "class" => "align_center"));
		$table->construct_row();
	}
	
	if($table->num_rows() == 0)
	{
		$table->construct_cell($lang->no_warning_types, array('colspan' => 5));
		$table->construct_row();
		$no_results = true;
	}
	
	$table->output($lang->warning_types);

	$page->output_footer();
}

function fetch_time_length($time, $period)
{
		$time = intval($time);
		if($period == "hours")
		{
			$time = $time*3600;
		}
		else if($period == "days")
		{
			$time = $time*86400;
		}
		else if($period == "weeks")
		{
			$time = $time*604800;
		}
		else if($period == "months")
		{
			$time = $time*2592000;
		}
		else
		{
			$time = 0;
		}
		return $time;
}
?>