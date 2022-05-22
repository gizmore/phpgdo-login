<?php
namespace GDO\Login;

use GDO\Core\GDO;
use GDO\Core\GDT_AutoInc;
use GDO\User\GDT_User;
use GDO\Net\GDT_IP;
use GDO\Core\GDT_CreatedAt;

/**
 * Login history GDO entity / table.
 * 
 * @author gizmore
 * @version 7.0.1
 * @since 3.1.0
 */
class GDO_LoginHistory extends GDO
{
	public function gdoCached() : bool { return false; }
	public function gdoColumns() : array
	{
		return array(
			GDT_AutoInc::make('lh_id'),
			GDT_User::make('lh_user_id'),
			GDT_IP::make('lh_ip'),
			GDT_CreatedAt::make('lh_authenticated_at'),
		);
	}
}
