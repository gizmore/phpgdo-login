<?php
namespace GDO\Login\Method;

use GDO\Core\GDT_Hook;
use GDO\Core\Method;
use GDO\Session\GDO_Session;
use GDO\User\GDO_User;

/**
 * Logout method.
 *
 * @version 7.0.0
 * @since 1.0.0
 * @author gizmore
 */
final class Logout extends Method
{

	public function getMethodTitle(): string
	{
		return t('logout');
	}

	public function getMethodDescription(): string
	{
		return t('logout');
	}

	public function execute()
	{
		$user = GDO_User::current();
		GDT_Hook::callHook('BeforeLogout', $user);
		if ($session = GDO_Session::instance())
		{
			$session->setVar('sess_user', null);
			$session->setVar('sess_data', null);
			$session->save();
		}
		GDO_User::setCurrent(GDO_User::ghost());
		GDT_Hook::callWithIPC('UserLoggedOut', $user);
		return $this->message('msg_logged_out');
	}

}
