<?php
namespace GDO\Login\Method;

use GDO\Admin\MethodAdmin;
use GDO\Core\GDT;
use GDO\Form\GDT_AntiCSRF;
use GDO\Form\GDT_Form;
use GDO\Form\GDT_Submit;
use GDO\Form\MethodForm;
use GDO\Login\Module_Login;
use GDO\User\GDT_User;

/**
 * Login as any user.
 * Requires admin permission.
 * Can be disabled in Module_Login.
 *
 * @version 7.0.0
 * @since 3.1.0
 * @author gizmore
 * @see Module_Login
 */
final class LoginAs extends MethodForm
{

	use MethodAdmin;

	public function getMethodTitle(): string
	{
		return t('login_as');
	}

	public function getMethodDescription(): string
	{
		return $this->getMethodTitle();
	}

	##############
	### Method ###
	##############
	public function isEnabled(): bool
	{
		return Module_Login::instance()->cfgLoginAs();
	}

	##################
	### MethodForm ###
	##################
	protected function createForm(GDT_Form $form): void
	{
		$form->addField(GDT_User::make('user_name')->notNull());
		$form->addField(GDT_AntiCSRF::make());
		$form->actions()->addField(GDT_Submit::make()->label('btn_login_as'));
	}

	public function formValidated(GDT_Form $form): GDT
	{
		$user = $form->getField('user_name')->getUser();
		return $this->loginForm()->loginSuccess($user);
	}

	/**
	 * Get the login form to login the selected user.
	 * Re-use it to authenticate.
	 */
	private function loginForm(): Form
	{
		return Form::make();
	}

}
