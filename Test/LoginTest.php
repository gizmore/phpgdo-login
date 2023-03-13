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

		$p = [
			'login' => 'gizmore',
			'password' => '11111111',
			'bind_ip' => '0',
			'submit' => '1',
		];
		$m = GDT_MethodTest::make()->method(Form::make());
		$m->inputs($p)->execute();
		$this->assert200('Check if login has no error.');

		$user1 = GDO_User::current();
		$user2 = $this->gizmore();

		assertTrue($user1->getID() === $user2->getID(), 'Check if gizmore can login.');
		assertTrue($user1 === $user2, 'Check if process cache works if gizmore can login.');
	}

	public function testLogout()
	{
		GDT_MethodTest::make()->method(Logout::make())->execute();
		$user = GDO_User::current();
		assertFalse($user->isUser(), 'Test if user can logout.');
	}

	public function testLogoutAndLoginBlocked()
	{
		$this->userGhost();

		# Trigger ban!
		$parameters = array(
			'login' => 'gizmore',
			'password' => 'incorrect',
			'bind_ip' => '0',
			'submit' => '1',
		);
		for ($i = 0; $i < 4; $i++)
		{
			GDT_MethodTest::make()->method(Form::make())
				->inputs($parameters)
				->execute();
		}

		$response = GDT_Page::instance()->topResponse();
		$html = $response->render();
		assertStringContainsString('Please wait', $html,
			'Check if login is blocked after N attempts.');
	}

}
