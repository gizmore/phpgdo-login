<?php
namespace GDO\Login\lang;

return [
	'module_login' => 'Login',
	'mt_login_form' => 'Login',
	'tt_login' => 'Enter your username or email.',
	'tt_bind_ip' => 'Lock your session to your current IP.',
	'login' => 'Nutzerschlüssel',

	'bind_ip' => 'Sitzung an IP binden?',
	'btn_login' => 'Einloggen',
	'btn_logout' => 'Ausloggen (%s)',
	'logout' => 'Abmelden',
	'msg_logged_out' => 'Sie sind nun ausgeloggt.',
	'msg_authenticated' => 'Willkommen zurück. Sie sind nun authentifiziert als %s.<br/>Schauen Sie im rechten Menü für individuelle Funktionen.',

	'err_login_failed' => 'Einloggen fehlgeschlagen. Sie haben noch %s Versuch(e) bis Sie für %s geblockt werden.',
	'err_login_ban' => 'Bitte warten Sie %s bevor Sie es erneut versuchen.',
#########
	'mail_subj_login_threat' => '[%s] Authentifizierungsversuch',
	'mail_body_login_threat' => '
Hallo %s,

Für Ihr Konto gab es einen fehlgeschlagenen Authentifizierungsversuch von dieser IP.

%3$s

Bitte beachten Sie das es vorerst keine weiteren Warnungen gibt, falls Ihr Konto wirklich angegriffen werden sollte.

Viele Grüße,
Das %2$s Team
',
#########
	'login_as' => 'Anmelden als',
	'msg_back_to' => 'Klicken Sie <a href="%s">hier</a> um fortzufahren.',
];
