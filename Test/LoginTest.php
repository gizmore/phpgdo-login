<?php
namespace GDO\Login\Test;

use GDO\Tests\GDT_MethodTest;
use GDO\Tests\TestCase;
use GDO\Login\Method\Form;
use GDO\Login\Method\Logout;
use function PHPUnit\Framework\assertTrue;
use function PHPUnit\Framework\assertFalse;
use GDO\User\GDO_User;
use function PHPUnit\Framework\assertStringContainsString;
use GDO\Language\Trans;
use GDO\UI\GDT_Page;

/**
 * Login test.
 *
 * @author gizmore
 */
final class LoginTest extends TestCase
{

	public function testLoginSuccess()
	{
		$this->userGhost();

		$parameters = array(
			'login' => 'gizmore',
			'password' => '11111111',
			'bindip' => '0',
		);

		$m = GDT_MethodTest::make()->method(Form::make())->inputs(
			$parameters);
		$m->execute();

		$user1 = GDO_User::current();
		$user2 = $this->gizmore();

		assertTrue($user1 === $user2,
			'Check if gizmore can login.');
	}

	public function testLogout()
	{
		//
		Trans::setISO('en'); # some stupid bug?
		$user = GDO_User::current();
		GDT_MethodTest::make()->method(Logout::make())->execute();
		assertFalse($user->isUser(), 'Test if user can logout.');
	}

	public function testLogoutAndLoginBlocked()
	{

		# Trigger ban!
		$parameters = array(
			'login' => 'gizmore',
			'password' => 'incorrect',
			'bindip' => '0',
		);
		for ($i = 0; $i < 4; $i++)
		{
			GDT_MethodTest::make()->method(Form::make())
				->inputs($parameters)
				->execute();
		}
		$response = GDT_Page::instance()->topResponse();
		$html = $response->renderMode();
		assertStringContainsString('Please wait', $html,
			'Check if login is blocked after N attempts.');
	}

}