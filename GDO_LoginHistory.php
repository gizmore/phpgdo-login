<?php
namespace GDO\Login;

use GDO\Core\GDO;
use GDO\Core\GDT_AutoInc;
use GDO\Core\GDT_CreatedAt;
use GDO\Net\GDT_IP;
use GDO\User\GDT_User;

/**
 * Login history GDO entity / table.
 *
 * @version 7.0.0
 * @since 3.1.0
 * @author gizmore
 */
class GDO_LoginHistory extends GDO
{

	public function gdoCached(): bool { return false; }

	public function gdoColumns(): array
	{
		return [
			GDT_AutoInc::make('lh_id'),
			GDT_User::make('lh_user_id'),
			GDT_IP::make('lh_ip'),
			GDT_CreatedAt::make('lh_authenticated_at'),
		];
	}

}
