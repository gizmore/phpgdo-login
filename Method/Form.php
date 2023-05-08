<?php
declare(strict_types=1);
namespace GDO\Login\Method;

use GDO\Captcha\GDT_Captcha;
use GDO\Core\Application;
use GDO\Core\GDO;
use GDO\Core\GDT;
use GDO\Core\GDT_Checkbox;
use GDO\Core\GDT_Hook;
use GDO\Core\GDT_Response;
use GDO\Core\GDT_String;
use GDO\Crypto\GDT_Password;
use GDO\Date\Time;
use GDO\DBMS\Module_DBMS;
use GDO\Form\GDT_Form;
use GDO\Form\GDT_Submit;
use GDO\Form\GDT_Validator;
use GDO\Form\MethodForm;
use GDO\Login\GDO_LoginAttempt;
use GDO\Login\Module_Login;
use GDO\Mail\Mail;
use GDO\Net\GDT_IP;
use GDO\Net\GDT_Url;
use GDO\Session\GDO_Session;
use GDO\User\GDO_User;

/**
 * Login via GDOv7 credentials.
 * Form and Method.
 *
 * @version 7.0.3
 * @since 1.0.0
 * @author gizmore
 */
final class Form extends MethodForm
{

	public function checkPermission(GDO_User $user, bool $silent = false): ?GDT
	{
		return null;
	}

	public function getMethodTitle(): string
	{
		return t('login');
	}

	public function getMethodDescription(): string
	{
		return t('login');
	}

	public function isUserRequired(): bool { return false; }

	public function getUserType(): ?string { return 'ghost,guest'; }

	protected function createForm(GDT_Form $form): void
	{
		$form->action(href('Login', 'Form'));
		$login = GDT_String::make('login')->icon('face')->tooltip('tt_login')->notNull();
		$form->addField($login);
		$form->addField(GDT_Validator::make('validateDeleted')->validator($form, $login, [$this, 'validateDeleted']));
		$form->addField(GDT_Validator::make('validateType')->validator($form, $login, [$this, 'validateType']));
		$form->addField(GDT_Password::make('password')->notNull());
		$form->addField(GDT_Checkbox::make('bind_ip')->tooltip('tt_bind_ip')->initial('1'));
		$form->addField(GDT_Url::make('_backto')->allowInternal()->hidden());
		if (Module_Login::instance()->cfgCaptcha())
		{
			$form->addField(GDT_Captcha::make());
		}
		$form->actions()->addField(GDT_Submit::make()->label('btn_login'));
		GDT_Hook::callHook('LoginForm', $form);
	}

	public function formValidated(GDT_Form $form): GDT
	{
		return $this->onLogin($form->getFormVar('login'), $form->getFormVar('password'), $form->getFormValue('bind_ip'));
	}

	public function onLogin(string $login, string $password, bool $bindIP = false): GDT
	{
		if ($response = $this->banCheck())
		{
			return $response->addField($this->renderPage());
		}
		$user = GDO_User::getByLogin($login);
		$hash = $user ? Module_Login::instance()->userSettingValue($user, 'password') : null;
		if (
			(!$user) ||
			(!$hash) ||
			(!$hash->validate($password))
		)
		{
			return $this->loginFailed($user)->addField($this->renderPage());
		}
		return $this->loginSuccess($user, $bindIP);
	}

	private function banCheck(): ?GDT
	{
		[$mintime, $count] = $this->banData();
		if ($count >= $this->maxAttempts())
		{
			$bannedFor = $mintime - $this->banCut();
			return $this->error('err_login_ban', [Time::humanDuration($bannedFor)]);
		}
		return null;
	}

	private function banData(): ?array
	{
		$dbms = Module_DBMS::instance();
		$table = GDO_LoginAttempt::table();
		$condition = sprintf('la_ip=%s AND la_time > ' . $dbms->dbmsFromUnixtime($this->banCut()), GDO::quoteS(GDT_IP::current()));
		return $table->select($dbms->dbmsTimestamp('MIN(la_time)') . ', COUNT(*)')->where($condition)->exec()->fetchRow();
	}

	private function banCut(): int { return Application::$TIME - $this->banTimeout(); }

	################
	### Security ###
	################

	private function banTimeout(): int { return Module_Login::instance()->cfgFailureTimeout(); }

	private function maxAttempts(): int { return Module_Login::instance()->cfgFailureAttempts(); }

	public function loginFailed($user): GDT
	{
		# Insert attempt
		$ip = GDT_IP::current();
		$userid = $user ? $user->getID() : null;
		GDO_LoginAttempt::blank([
			'la_ip' => $ip,
			'la_user_id' => $userid,
		])->insert();

		# Count victim attack. If only 1, we got a new threat and mail it.
		if ($user)
		{
			$this->checkSecurityThreat($user);
		}

		# Count attacker attempts
		[$mintime, $attempts] = $this->banData();
		$bannedFor = $mintime - $this->banCut();
		$attemptsLeft = $this->maxAttempts() - $attempts;

		return $this->error('err_login_failed', [$attemptsLeft, Time::humanDuration($bannedFor)], 200);
	}

	private function checkSecurityThreat(GDO_User $user): void
	{
		$dbms = Module_DBMS::instance();
		$table = GDO_LoginAttempt::table();
		$fromUnix = $dbms->dbmsFromUnixtime($this->banCut());
		$condition = sprintf('la_user_id=%s AND la_time > %s',
			$user->getID(), $fromUnix);
		if (1 === $table->countWhere($condition))
		{
			if (module_enabled('Mail'))
			{
				$this->mailSecurityThreat($user);
			}
		}
	}

	private function mailSecurityThreat(GDO_User $user): void
	{
		$mail = new Mail();
		$mail->setSender(GDO_BOT_EMAIL);
		$mail->setSubject(t('mail_subj_login_threat', [sitename()]));
		$revealIP = Module_Login::instance()->cfgFailureIPReveal();
		$ip = $revealIP ? GDT_IP::current() : 'xx.xx.xx.xx';
		$args = [$user->renderName(), sitename(), $ip];
		$mail->setBody(t('mail_body_login_threat', $args));
		$mail->sendToUser($user);
	}

	public function loginSuccess(GDO_User $user, bool $bindIP = false): GDT
	{
		if (module_enabled('Session'))
		{
			if (!($session = GDO_Session::instance()))
			{
				return $this->error('err_session_required');
			}
			$session->setVar('sess_user', $user->getID());
			GDO_User::setCurrent($user);
			if ($bindIP)
			{
				$session->setVar('sess_ip', GDT_IP::current());
			}
			GDT_Hook::callWithIPC('UserAuthenticated', $user);
			$this->message('msg_authenticated', [$user->renderUserName()]);
			if ($href = $this->gdoParameterVar('_backto'))
			{
				$this->message('msg_back_to', [html($href)]);
			}
		}
		else
		{
			return $this->error('err_session_required');
		}
		return GDT_Response::make();
	}

	/**
	 * Validate if the user to authenticate is deleted.
	 */
	public function validateDeleted(GDT_Form $form, GDT $field, $value): bool
	{
		if ($user = GDO_User::getByLogin($value))
		{
			if ($user->isDeleted())
			{
				return $field->error('err_user_deleted');
			}
		}
		return true;
	}

	/**
	 * Disallow system or bot users.
	 */
	public function validateType(GDT_Form $form, GDT $field, $value): bool
	{
		if ($value)
		{
			if ($user = GDO_User::getByLogin($value))
			{
				if (!$user->isMember())
				{
					return $field->error('err_user_type', [t('enum_member')]);
				}
			}
		}
		return true;
	}

}
