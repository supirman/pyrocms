<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Settings module
 *
 * @author PyroCMS Dev Team
 * @package PyroCMS\Core\Modules\Settings
 */
class Module_Settings extends Module {

	public $version = '1.0';

	public function info()
	{
		return array(
			'name' => array(
				'en' => 'Settings',
				'ar' => 'الإعدادات',
				'br' => 'Configurações',
				'cs' => 'Nastavení',
				'da' => 'Indstillinger',
				'de' => 'Einstellungen',
				'el' => 'Ρυθμίσεις',
				'es' => 'Configuraciones',
				'fi' => 'Asetukset',
				'fr' => 'Paramètres',
				'he' => 'הגדרות',
				'id' => 'Pengaturan',
				'it' => 'Impostazioni',
				'lt' => 'Nustatymai',
				'nl' => 'Instellingen',
				'pl' => 'Ustawienia',
				'ru' => 'Настройки',
				'sl' => 'Nastavitve',
				'zh' => '網站設定',
			),
			'description' => array(
				'en' => 'Allows administrators to update settings like Site Name, messages and email address, etc.',
				'ar' => 'تمكن المدراء من تحديث الإعدادات كإسم الموقع، والرسائل وعناوين البريد الإلكتروني، .. إلخ.',
				'br' => 'Permite com que administradores e a equipe consigam trocar as configurações do website incluindo o nome e descrição.',
				'cs' => 'Umožňuje administrátorům měnit nastavení webu jako jeho jméno, zprávy a emailovou adresu apod.',
				'da' => 'Lader administratorer opdatere indstillinger som sidenavn, beskeder og email adresse, etc.',
				'de' => 'Erlaubt es Administratoren die Einstellungen der Seite wie Name und Beschreibung zu ändern.',
				'el' => 'Επιτρέπει στους διαχειριστές να τροποποιήσουν ρυθμίσεις όπως το Όνομα του Ιστοτόπου, τα μηνύματα και τις διευθύνσεις email, κ.α.',
				'es' => 'Permite a los administradores y al personal configurar los detalles del sitio como el nombre del sitio y la descripción del mismo.',
				'fi' => 'Mahdollistaa sivuston asetusten muokkaamisen, kuten sivuston nimen, viestit ja sähköpostiosoitteet yms.',
				'fr' => 'Permet aux admistrateurs et au personnel de modifier les paramètres du site : nom du site et description',
				'he' => 'ניהול הגדרות שונות של האתר כגון: שם האתר, הודעות, כתובות דואר וכו',
				'id' => 'Memungkinkan administrator untuk dapat memperbaharui pengaturan seperti nama situs, pesan dan alamat email, dsb.',
				'it' => 'Permette agli amministratori di aggiornare impostazioni quali Nome del Sito, messaggi e indirizzo email, etc.',
				'lt' => 'Leidžia administratoriams keisti puslapio vavadinimą, žinutes, administratoriaus el. pašta ir kitą.',
				'nl' => 'Maakt het administratoren en medewerkers mogelijk om websiteinstellingen zoals naam en beschrijving te veranderen.',
				'pl' => 'Umożliwia administratorom zmianę ustawień strony jak nazwa strony, opis, e-mail administratora, itd.',
				'ru' => 'Управление настройками сайта - Имя сайта, сообщения, почтовые адреса и т.п.',
				'sl' => 'Dovoljuje administratorjem posodobitev nastavitev kot je Ime strani, sporočil, email naslova itd.',
				'zh' => '網站管理者可更新的重要網站設定。例如：網站名稱、訊息、電子郵件等。',
			),
			'frontend' => false,
			'backend'  => true,
			'skip_xss' => true,
			'menu'	  => false,
		);
	}

	public function install()
	{
		$this->dbforge->drop_table('settings');

		log_message('debug', '-- Settings: going to install the settings table');
		$tables = array(
			'settings' => array(
				'slug' => array('type' => 'VARCHAR', 'constraint' => 30, 'primary' => true, 'unique' => true, 'key' => 'index_slug'),
				'title' => array('type' => 'VARCHAR', 'constraint' => 100,),
				'description' => array('type' => 'TEXT',),
				'type' => array('type' => 'set',  'constraint' => array('text','textarea','password','select','select-multiple','radio','checkbox'),),
				'default' => array('type' => 'TEXT',),
				'value' => array('type' => 'TEXT',),
				'options' => array('type' => 'VARCHAR', 'constraint' => 255,),
				'is_required' => array('type' => 'INT', 'constraint' => 1,),
				'is_gui' => array('type' => 'INT', 'constraint' => 1,),
				'module' => array('type' => 'VARCHAR', 'constraint' => 50,),
				'order' => array('type' => 'INT', 'constraint' => 10, 'default' => 0,),
			),
		);

		if ( ! $this->install_tables($tables))
		{
			return false;
		}
		log_message('debug', '-- -- ok settings table');

		log_message('debug', '-- Settings: going to install the default settings');
		// Regarding ordering: any additions to this table can have an order
		// value the same as a sibling in the same section. For example if you
		// add to the Email tab give it a value in the range of 983 to 975.
		// Third-party modules should use lower numbers or 0.
		$settings = array(
			'site_name' => array(
				'title' => 'Site Name',
				'description' => 'The name of the website for page titles and for use around the site.',
				'type' => 'text',
				'default' => 'Un-named Website',
				'value' => '',
				'options' => '',
				'is_required' => 1,
				'is_gui' => 1,
				'module' => '',
				'order' => 1000,
			),
			'site_slogan' => array(
				'title' => 'Site Slogan',
				'description' => 'The slogan of the website for page titles and for use around the site',
				'type' => 'text',
				'default' => '',
				'value' => 'Add your slogan here',
				'options' => '',
				'is_required' => 0,
				'is_gui' => 1,
				'module' => '',
				'order' => 999,
			),
			'meta_topic' => array(
				'title' => 'Meta Topic',
				'description' => 'Two or three words describing this type of company/website.',
				'type' => 'text',
				'default' => 'Content Management',
				'value' => 'Add your slogan here',
				'options' => '',
				'is_required' => 0,
				'is_gui' => 1,
				'module' => '',
				'order' => 998,
			),
			'site_lang' => array(
				'title' => 'Site Language',
				'description' => 'The native language of the website, used to choose templates of e-mail notifications, contact form, and other features that should not depend on the language of a user.',
				'type' => 'select',
				'default' => DEFAULT_LANG,
				'value' => DEFAULT_LANG,
				'options' => 'func:get_supported_lang',
				'is_required' => 1,
				'is_gui' => 1,
				'module' => '',
				'order' => 997,
			),
			'site_public_lang' => array(
				'title' => 'Public Languages',
				'description' => 'Which are the languages really supported and offered on the front-end of your website?',
				'type' => 'checkbox',
				'default' => DEFAULT_LANG,
				'value' => DEFAULT_LANG,
				'options' => 'func:get_supported_lang',
				'is_required' => 1,
				'is_gui' => 1,
				'module' => '',
				'order' => 996,
			),
			'date_format' => array(
				'title' => 'Date Format',
				'description' => 'How should dates be displayed across the website and control panel? Using the <a target=\"_blank\" href=\"http://php.net/manual/en/function.date.php\">date format</a> from PHP - OR - Using the format of <a target=\"_blank\" href=\"http://php.net/manual/en/function.strftime.php\">strings formatted as date</a> from PHP.',
				'type' => 'text',
				'default' => 'Y-m-d',
				'value' => '',
				'options' => '',
				'is_required' => 1,
				'is_gui' => 1,
				'module' => '',
				'order' => 995,
			),
			'currency' => array(
				'title' => 'Currency',
				'description' => 'The currency symbol for use on products, services, etc.',
				'type' => 'text',
				'default' => '&pound;',
				'value' => '',
				'options' => '',
				'is_required' => 1,
				'is_gui' => 1,
				'module' => '',
				'order' => 994,
			),
			// @todo Move this to the respective module
			'ckeditor_config' => array(
				'title' => 'CKEditor Config',
				'description' => 'You can find a list of valid configuration items in <a target=\"_blank\" href=\"http://docs.cksource.com/ckeditor_api/symbols/CKEDITOR.config.html\">CKEditor\'s documentation.</a>',
				'type' => 'textarea',
				'default' => '',
				'value' => "{{# this is the config for all wysiwyg-simple textareas #}}\n$('textarea.wysiwyg-simple').ckeditor({\n	toolbar: [\n		['Bold', 'Italic', '-', 'NumberedList', 'BulletedList', '-', 'Link', 'Unlink']\n	  ],\n	width: '99%',\n	height: 100,\n	dialog_backgroundCoverColor: '#000',\n	defaultLanguage: '{{ helper:config item=\"default_language\" }}',\n	language: '{{ global:current_language }}'\n});\n\n{{# this is a wysiwyg-simple editor customized for the blog module (it allows images to be inserted) #}}\n$('textarea.blog.wysiwyg-simple').ckeditor({\n	toolbar: [\n		['pyroimages'],\n		['Bold', 'Italic', '-', 'NumberedList', 'BulletedList', '-', 'Link', 'Unlink']\n	  ],\n	extraPlugins: 'pyroimages',\n	width: '99%',\n	height: 100,\n	dialog_backgroundCoverColor: '#000',\n	defaultLanguage: '{{ helper:config item=\"default_language\" }}',\n	language: '{{ global:current_language }}'\n});\n\n{{# and this is the advanced editor #}}\n$('textarea.wysiwyg-advanced').ckeditor({\n	toolbar: [\n		['Maximize'],\n		['pyroimages', 'pyrofiles'],\n		['Cut','Copy','Paste','PasteFromWord'],\n		['Undo','Redo','-','Find','Replace'],\n		['Link','Unlink'],\n		['Table','HorizontalRule','SpecialChar'],\n		['Bold','Italic','StrikeThrough'],\n		['JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock','-','BidiLtr','BidiRtl'],\n		['Format', 'FontSize', 'Subscript','Superscript', 'NumberedList','BulletedList','Outdent','Indent','Blockquote'],\n		['ShowBlocks', 'RemoveFormat', 'Source']\n	],\n	extraPlugins: 'pyroimages,pyrofiles',\n	width: '99%',\n	height: 400,\n	dialog_backgroundCoverColor: '#000',\n	removePlugins: 'elementspath',\n	defaultLanguage: '{{ helper:config item=\"default_language\" }}',\n	language: '{{ global:current_language }}'\n});",
				'options' => '',
				'is_required' => 1,
				'is_gui' => 1,
				'module' => 'wysiwyg',
				'order' => 993,
			),
			'records_per_page' => array(
				'title' => 'Records Per Page',
				'description' => 'How many records should we show per page in the admin section?',
				'type' => 'select',
				'default' => '25',
				'value' => '',
				'options' => '10=10|25=25|50=50|100=100',
				'is_required' => 1,
				'is_gui' => 1,
				'module' => '',
				'order' => 992,
			),
			'rss_feed_items' => array(
				'title' => 'Feed item count',
				'description' => 'How many items should we show in RSS/blog feeds?',
				'type' => 'select',
				'default' => '25',
				'value' => '',
				'options' => '10=10|25=25|50=50|100=100',
				'is_required' => 1,
				'is_gui' => 1,
				'module' => '',
				'order' => 991,
			),
			'dashboard_rss' => array(
				'title' => 'Dashboard RSS Feed',
				'description' => 'Link to an RSS feed that will be displayed on the dashboard.',
				'type' => 'text',
				'default' => 'http://feeds.feedburner.com/pyrocms-installed',
				'value' => '',
				'options' => '10=10|25=25|50=50|100=100',
				'is_required' => 0,
				'is_gui' => 1,
				'module' => '',
				'order' => 990,
			),
			'dashboard_rss_count' => array(
				'title' => 'Dashboard RSS Items',
				'description' => 'How many RSS items would you like to display on the dashboard?',
				'type' => 'text',
				'default' => '5',
				'value' => '5',
				'options' => '10=10|25=25|50=50|100=100',
				'is_required' => 1,
				'is_gui' => 1,
				'module' => '',
				'order' => 989,
			),
			'frontend_enabled' => array(
				'title' => 'Site Status',
				'description' => 'Use this option to the user-facing part of the site on or off. Useful when you want to take the site down for maintenance.',
				'type' => 'radio',
				'default' => true,
				'value' => '',
				'options' => '1=Open|0=Closed',
				'is_required' => 1,
				'is_gui' => 1,
				'module' => '',
				'order' => 988,
			),
			'unavailable_message' => array(
				'title' => 'Unavailable Message',
				'description' => 'When the site is turned off or there is a major problem, this message will show to users.',
				'type' => 'textarea',
				'default' => 'Sorry, this website is currently unavailable.',
				'value' => '',
				'options' => '1=Open|0=Closed',
				'is_required' => 0,
				'is_gui' => 1,
				'module' => '',
				'order' => 987,
			),
			// @todo Move this to the respective module
			'files_cache' => array(
				'title' => 'Files Cache',
				'description' => 'When outputting an image via site.com/files what shall we set the cache expiration for?',
				'type' => 'select',
				'default' => '480',
				'value' => '480',
				'options' => '0=no-cache|1=1-minute|60=1-hour|180=3-hour|480=8-hour|1440=1-day|43200=30-days',
				'is_required' => 1,
				'is_gui' => 1,
				'module' => 'files',
				'order' => 986,
			),
			// @todo Move this to the respective module
			'ga_tracking' => array(
				'title' => 'Google Tracking Code',
				'description' => 'Enter your Google Analytic Tracking Code to activate Google Analytics view data capturing. E.g: UA-19483569-6',
				'type' => 'text',
				'default' => '',
				'value' => '',
				'options' => '',
				'is_required' => 0,
				'is_gui' => 1,
				'module' => 'integration',
				'order' => 985,
			),
			// @todo Move this to the respective module
			'ga_profile' => array(
				'title' => 'Google Analytic Profile ID',
				'description' => 'Profile ID for this website in Google Analytics',
				'type' => 'text',
				'default' => '',
				'value' => '',
				'options' => '',
				'is_required' => 0,
				'is_gui' => 1,
				'module' => 'integration',
				'order' => 984,
			),
			// @todo Move this to the respective module
			'ga_email' => array(
				'title' => 'Google Analytic E-mail',
				'description' => 'E-mail address used for Google Analytics, we need this to show the graph on the dashboard.',
				'type' => 'text',
				'default' => '',
				'value' => '',
				'options' => '',
				'is_required' => 0,
				'is_gui' => 1,
				'module' => 'integration',
				'order' => 983,
			),
			// @todo Move this to the respective module
			'ga_password' => array(
				'title' => 'Google Analytic Password',
				'description' => 'This is also needed this to show the graph on the dashboard.',
				'type' => 'text',
				'default' => '',
				'value' => '',
				'options' => '',
				'is_required' => 0,
				'is_gui' => 1,
				'module' => 'integration',
				'order' => 982,
			),
			// @todo Move this to the respective module
			'akismet_api_key' => array(
				'title' => 'Akismet API Key',
				'description' => 'Akismet is a spam-blocker from the WordPress team. It keeps spam under control without forcing users to get past human-checking CAPTCHA forms.',
				'type' => 'text',
				'default' => '',
				'value' => '',
				'options' => '',
				'is_required' => 0,
				'is_gui' => 1,
				'module' => 'integration',
				'order' => 981,
			),
			'contact_email' => array(
				'title' => 'Contact E-mail',
				'description' => 'All e-mails from users, guests and the site will go to this e-mail address.',
				'type' => 'text',
				'default' => DEFAULT_EMAIL,
				'value' => '',
				'options' => '',
				'is_required' => 1,
				'is_gui' => 1,
				'module' => 'email',
				'order' => 979,
			),
			'server_email' => array(
				'title' => 'Server E-mail',
				'description' => 'All e-mails to users will come from this e-mail address.',
				'type' => 'text',
				'default' => 'admin@localhost',
				'value' => '',
				'options' => '',
				'is_required' => 1,
				'is_gui' => 1,
				'module' => 'email',
				'order' => 978,
			),
			'mail_protocol' => array(
				'title' => 'Mail Protocol',
				'description' => 'Select desired email protocol.',
				'type' => 'select',
				'default' => 'mail',
				'value' => 'mail',
				'options' => 'mail=Mail|sendmail=Sendmail|smtp=SMTP',
				'is_required' => 1,
				'is_gui' => 1,
				'module' => 'email',
				'order' => 977,
			),
			'mail_smtp_host' => array(
				'title' => 'SMTP Host Name',
				'description' => 'The host name of your smtp server.',
				'type' => 'text',
				'default' => '',
				'value' => '',
				'options' => '',
				'is_required' => 0,
				'is_gui' => 1,
				'module' => 'email',
				'order' => 976,
			),
			'mail_smtp_pass' => array(
				'title' => 'SMTP password',
				'description' => 'SMTP password.',
				'type' => 'password',
				'default' => '',
				'value' => '',
				'options' => '',
				'is_required' => 0,
				'is_gui' => 1,
				'module' => 'email',
				'order' => 975,
			),
			'mail_smtp_port' => array(
				'title' => 'SMTP Port',
				'description' => 'SMTP port number.',
				'type' => 'text',
				'default' => '',
				'value' => '',
				'options' => '',
				'is_required' => 0,
				'is_gui' => 1,
				'module' => 'email',
				'order' => 974,
			),
			'mail_smtp_user' => array(
				'title' => 'SMTP User Name',
				'description' => 'SMTP user name.',
				'type' => 'text',
				'default' => '',
				'value' => '',
				'options' => '',
				'is_required' => 0,
				'is_gui' => 1,
				'module' => 'email',
				'order' => 973,
			),
			'mail_sendmail_path' => array(
				'title' => 'Sendmail Path',
				'description' => 'Path to server sendmail binary.',
				'type' => 'text',
				'default' => '',
				'value' => '',
				'options' => '',
				'is_required' => 0,
				'is_gui' => 1,
				'module' => 'email',
				'order' => 972,
			),
			// @todo 'twitter_*' settings are not used anywhere, maybe remove this? (Check thouroughly first)
			'twitter_username' => array(
				'title' => 'Username',
				'description' => 'Twitter username.',
				'type' => 'text',
				'default' => '',
				'value' => '',
				'options' => '',
				'is_required' => 0,
				'is_gui' => 1,
				'module' => 'twitter',
				'order' => 971,
			),
			'twitter_feed_count' => array(
				'title' => 'Feed Count',
				'description' => 'How many tweets should be returned to the Twitter feed block?',
				'type' => 'text',
				'default' => '5',
				'value' => '',
				'options' => '',
				'is_required' => 0,
				'is_gui' => 1,
				'module' => 'twitter',
				'order' => 970,
			),
			'twitter_cache' => array(
				'title' => 'Cache time',
				'description' => 'How many minutes should your Tweets be stored?',
				'type' => 'text',
				'default' => '300',
				'value' => '',
				'options' => '',
				'is_required' => 0,
				'is_gui' => 1,
				'module' => 'twitter',
				'order' => 969,
			),
			// @todo Move this to the respective module
			'enable_comments' => array(
				'title' => 'Enable Comments',
				'description' => 'Enable comments.',
				'type' => 'radio',
				'default' => true,
				'value' => true,
				'options' => '1=Enabled|0=Disabled',
				'is_required' => 1,
				'is_gui' => 1,
				'module' => 'comments',
				'order' => 968,
			),
			// @todo Move this to the respective module
			'moderate_comments' => array(
				'title' => 'Moderate Comments',
				'description' => 'Force comments to be approved before they appear on the site.',
				'type' => 'radio',
				'default' => true,
				'value' => true,
				'options' => '1=Enabled|0=Disabled',
				'is_required' => 1,
				'is_gui' => 1,
				'module' => 'comments',
				'order' => 967,
			),
			// @todo Move this to the respective module
			'comment_order' => array(
				'title' => 'Comment Order',
				'description' => 'Sort order in which to display comments.',
				'type' => 'select',
				'default' => 'ASC',
				'value' => 'ASC',
				'options' => 'ASC=Oldest First|DESC=Newest First',
				'is_required' => 1,
				'is_gui' => 1,
				'module' => 'comments',
				'order' => 966,
			),
			// @todo Move this to the respective module
			'comment_markdown' => array(
				'title' => 'Allow Markdown',
				'description' => 'Do you want to allow visitors to post comments using Markdown?',
				'type' => 'select',
				'default' => '0',
				'value' => '0',
				'options' => '0=Text Only|1=Allow Markdown',
				'is_required' => 1,
				'is_gui' => 1,
				'module' => 'comments',
				'order' => 965,
			),
			// @todo Move this to the respective module
			'auto_username' => array(
				'title' => 'Auto Username',
				'description' => 'Create the username automatically, meaning users can skip making one on registration.',
				'type' => 'radio',
				'default' => true,
				'value' => '',
				'options' => '1=Enabled|0=Disabled',
				'is_required' => 0,
				'is_gui' => 1,
				'module' => 'users',
				'order' => 964,
			),
			// @todo Move this to the respective module
			'enable_profiles' => array(
				'title' => 'Enable profiles',
				'description' => 'Allow users to add and edit profiles.',
				'type' => 'radio',
				'default' => true,
				'value' => '',
				'options' => '1=Enabled|0=Disabled',
				'is_required' => 1,
				'is_gui' => 1,
				'module' => 'users',
				'order' => 963,
			),
			// @todo Move this to the respective module
			'require_lastname' => array(
				'title' => 'Require last names?',
				'description' => 'For some situations, a last name may not be required. Do you want to force users to enter one or not?',
				'type' => 'radio',
				'default' => true,
				'value' => '',
				'options' => '1=Required|0=Optional',
				'is_required' => 1,
				'is_gui' => 1,
				'module' => 'users',
				'order' => 962,
			),
			// @todo Move this to the respective module
			'activation_email' => array(
				'title' => 'Activation Email',
				'description' => 'Send out an e-mail when a user signs up with an activation link. Disable this to let only admins activate accounts.',
				'type' => 'radio',
				'default' => true,
				'value' => '',
				'options' => '1=Enabled|0=Disabled',
				'is_required' => 0,
				'is_gui' => 1,
				'module' => 'users',
				'order' => 961,
			),
			// @todo Move this to the respective module
			'registered_email' => array(
				'title' => 'User Registered Email',
				'description' => 'Send a notification email to the contact e-mail when someone registers.',
				'type' => 'radio',
				'default' => true,
				'value' => '',
				'options' => '1=Enabled|0=Disabled',
				'is_required' => 0,
				'is_gui' => 1,
				'module' => 'users',
				'order' => 962,
			),
			// @todo Move this to the respective module
			'enable_registration' => array(
				'title' => 'Enable user registration',
				'description' => 'Allow users to register in your site.',
				'type' => 'radio',
				'default' => true,
				'value' => '',
				'options' => '1=Enabled|0=Disabled',
				'is_required' => 0,
				'is_gui' => 1,
				'module' => 'users',
				'order' => 961,
			),
			// @todo Move this to the respective module
			'default_theme' => array(
				'title' => 'Default Theme',
				'description' => 'Select the theme you want users to see by default.',
				'type' => '',
				'default' => 'base',
				'value' => 'base',
				'options' => 'func:get_themes',
				'is_required' => 1,
				'is_gui' => 0,
				'module' => '',
				'order' => 0,
			),
			// @todo Move this to the respective module
			'admin_theme' => array(
				'title' => 'Control Panel Theme',
				'description' => 'Select the theme for the control panel.',
				'type' => '',
				'default' => '',
				'value' => 'pyrocms',
				'options' => 'func:get_themes',
				'is_required' => 1,
				'is_gui' => 0,
				'module' => '',
				'order' => 0,
			),
			// @todo Move this to the respective module
			'admin_force_https' => array(
				'title' => 'Force HTTPS for Control Panel?',
				'description' => 'Allow only the HTTPS protocol when using the Control Panel?',
				'type' => 'radio',
				'default' => false,
				'value' => '',
				'options' => '1=Yes|0=No',
				'is_required' => 1,
				'is_gui' => 1,
				'module' => '',
				'order' => 0,
			),
			// @todo Move this to the respective module
			'addons_upload' => array(
				'title' => 'Addons Upload Permissions',
				'description' => 'Keeps mere admins from uploading addons by default',
				'type' => 'text',
				'default' => '0',
				'value' => '0',
				'options' => '',
				'is_required' => 1,
				'is_gui' => 0,
				'module' => '',
				'order' => 0,
			),
			// @todo Move this to the respective module
			'api_enabled' => array(
				'title' => 'API Enabled',
				'description' => 'Allow API access to all modules which have an API controller.',
				'type' => 'select',
				'`default`' => false,
				'value' => '0',
				'`options`' => '0=Disabled|1=Enabled',
				'is_required' => false,
				'is_gui' => false,
				'module' => 'api',
				'order' => 0,
			),
			// @todo Move this to the respective module
			'api_user_keys' => array(
				'title' => 'API User Keys',
				'description' => 'Allow users to sign up for API keys (if the API is Enabled).',
				'type' => 'select',
				'`default`' => false,
				'value' => '0',
				'`options`' => '0=Disabled|1=Enabled',
				'is_required' => false,
				'is_gui' => false,
				'module' => 'api',
				'order' => 0,
			),
		);

		// Lets add the settings for this module.
		foreach ($settings as $slug => $setting_info)
		{
			log_message('debug', '-- Settings: installing '.$slug);
			$setting_info['slug'] = $slug;
			if ( ! $this->db->insert('settings', $setting_info))
			{
				log_message('debug', '-- -- could not install '.$slug);
				return false;
			}
		}

		return true;

	}

	public function uninstall()
	{
		// This is a core module, lets keep it around.
		return false;
	}

	public function upgrade($old_version)
	{
		return true;
	}

}