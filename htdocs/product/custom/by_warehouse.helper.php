<?php

class estadovLockHelper
{
	public static function isLockedUp(&$user,&$db,$ticket_id)
	{
		$expire_time = 300;
		$gmNow= gmdate('Y-m-d H:i:s');
		$now = gmmktime(
						 substr($gmNow,11,2)
						,substr($gmNow,14,2)
						,substr($gmNow,17,2)
						,substr($gmNow, 5,2)
						,substr($gmNow, 8,2)
						,substr($gmNow, 0,4)
						);
		$expired = gmdate('Y-m-d H:i:s',$now - $expire_time);
		
		$ses =	session_id();

		$sql =	 'DELETE '."\r\n"
				.'FROM `llx_estadov_lockedup` '."\r\n"
				.'WHERE `last_time_used` < \''.$expired.'\' '."\r\n"
				;		
		if (!$res = $db->query($sql))
		{
			return -1; #Error
		}
		
		$sql =	 'SELECT * '."\r\n"
				.'FROM `llx_estadov_lockedup` '."\r\n"
				.'WHERE `fk_ticket`='.$ticket_id.' '."\r\n"
				;
		if (!$res = $db->query($sql))
		{
			return -1; #Error
		}
		
		if (!$row = $db->fetch_object($res))
		{
			return 0; #No blockeado
		}
		$time = gmmktime(
						 substr($row->last_time_used,11,2)
						,substr($row->last_time_used,14,2)
						,substr($row->last_time_used,17,2)
						,substr($row->last_time_used, 5,2)
						,substr($row->last_time_used, 8,2)
						,substr($row->last_time_used, 0,4)
						);
		
		
		//die(var_dump($row,$row->last_time_used,$expired));
		
		if (($row->fk_user == $user->id && $ses == $row->session_id) || $now > ($time + $expire_time))
		{
			return 0; # Locked by myself or expired (30 minutes)
		}
		else
		{
			return 1; # Blocked by anybody else and still active
		}
	}
	
	public static function updateLockUp(&$user,&$db,$ticket_id)
	{
		$ses =	session_id();
		$sql =	 'INSERT INTO `llx_estadov_lockedup`'."\r\n"
				.'(`fk_ticket`, `fk_user`, `session_id`, `last_time_used`)'."\r\n"
				.'VALUES'."\r\n"
				.'('.$ticket_id.', '.$user->id.', \''.$db->escape($ses).'\', \''.gmdate('Y-m-d H:i:s').'\')'."\r\n"
				.'ON DUPLICATE KEY UPDATE'."\r\n"
				.'`fk_user` = '.$user->id.','."\r\n"
				.'`session_id` = \''.$db->escape($ses).'\','."\r\n"
				.'`last_time_used` = \''.gmdate('Y-m-d H:i:s').'\''."\r\n"
				;
		if (!$db->query($sql))
		{
			return -1;
		}
		else
		{
			return 1;
		}
	}
}