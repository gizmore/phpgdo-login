<?php
namespace GDO\Login;

use GDO\Core\Debug;
use GDO\Core\GDO_Module;
use GDO\Core\GDT_Checkbox;
use GDO\Core\GDT_Int;
use GDO\Crypto\GDT_PasswordHash;
use GDO\Date\GDT_Duration;
use GDO\Register\GDO_UserActivation;
use GDO\UI\GDT_Link;
use GDO\UI\GDT_Page;
use GDO\User\GDO_User;
use GDO\User\GDT_ACLRelation;

/**
 * Login module for GDOv7.
 *
 * - Login History
 * - Bruteforce Protection
 * - Optional Captcha
 * - LoginAs any user (staff)
 * - Warnings on failed logins (optionally show attacker IP to affected user)
 *
 * @version 7.0.2
 * @since 3.0.0
 * @author gizmore@wechall.net
 */
final class Module_Login extends GDO_Module
{

	public int $priority = 150;

	##############
	### Module ###
	##############
	public function getClasses(): array
	{
		return [
			GDO_LoginAttempt::class,
			GDO_LoginHistory::class,
		];
	}

	public function onLoadLanguage(): void { $this->loadLanguage('lang/login'); }

	public function getDependencies(): array { return ['Session']; }

	public function getFriendencies(): array { return ['Captcha']; }

	##############
	### Config ###
	##############
	public function getConfig(): array
	{
		return [
			GDT_Checkbox::make('login_captcha')->initial('0'),
			GDT_Checkbox::make('login_history')->initial('1'),
			GDT_Duration::make('login_timeout')->initial('10m')->min(10)->max(72600),
			GDT_Int::make('login_tries')->initial('3')->min(1)->max(100),
			GDT_Checkbox::make('login_warning_ip_reveal')->initial('1'), # Do not censor IP in alert mails
			GDT_Checkbox::make('login_right_bar')->initial('1'),
			GDT_Checkbox::make('login_as')->initial('1'),
		];
	}

	public function getACLDefaults(): array
	{
		return [
			'password' => [GDT_ACLRelation::HIDDEN, '0', null],
		];
	}

	public function getUserConfig(): array
	{
		return [
			GDT_PasswordHash::make('password'),
		];
	}

	public function onInitSidebar(): void
	{
		if ($this->cfgRightBar())
		{
			$user = GDO_User::current();
			$navbar = GDT_Page::instance()->rightBar();
			if (!$user->isUser())
			{
				$navbar->addField(GDT_Link::make('signin')->text('btn_login')->href(href('Login', 'Form')));
			}
			else
			{
				$navbar->addField(GDT_Link::make('signout')->text('btn_logout', [$user->renderUserName()])->href(href('Login', 'Logout')));
			}
		}
	}

	public function cfgRightBar(): bool { return $this->getConfigValue('login_right_bar'); }

	public function cfgCaptcha(): bool { return module_enabled('Captcha') && $this->getConfigValue('login_captcha'); }

	public function cfgHistory(): bool { return $this->getConfigValue('login_history'); }

	public function cfgFailureTimeout(): int { return $this->getConfigValue('login_timeout'); }

	################
	### Settings ###
	################

	public function cfgFailureAttempts(): int { return $this->getConfigValue('login_tries'); }

	public function cfgFailureIPReveal(): bool { return $this->getConfigValue('login_warning_ip_reveal'); }

	##############
	### Navbar ###
	##############

	public function cfgLoginAs(): bool { return $this->getConfigValue('login_as'); }

	#############
	### Hooks ###
	#############

	public function hookUserActivated(GDO_User $user, ?GDO_UserActivation $activation): void
	{
		if ($activation)
		{
			if ($hash = $activation->getPasswordHash())
			{
				$this->saveUserSetting($user, 'password', $hash);
			}
		}
	}

}
