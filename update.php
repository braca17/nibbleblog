<?php

/*
 * Nibbleblog -
 * http://www.nibbleblog.com
 * Author Diego Najar

 * All Nibbleblog code is released under the GNU General Public License.
 * See COPYRIGHT.txt and LICENSE.txt.
*/

define('UPDATER_VERSION', '1.3');

// =====================================================================
// Require
// =====================================================================
require('admin/boot/rules/1-fs_php.bit');
require('admin/boot/rules/98-constants.bit');

require(PATH_DB . 'nbxml.class.php');
require(PATH_DB . 'db_settings.class.php');
require(PATH_DB . 'db_posts.class.php');

require(PATH_HELPERS . 'html.class.php');
require(PATH_HELPERS . 'date.class.php');
require(PATH_HELPERS . 'text.class.php');
require(PATH_HELPERS . 'filesystem.class.php');

// =====================================================================
// DB
// =====================================================================
$_DB_SETTINGS	= new DB_SETTINGS( FILE_XML_CONFIG );
$_DB_POST		= new DB_POSTS( FILE_XML_POSTS );

// =====================================================================
// Variables
// =====================================================================
$blog_domain = getenv('HTTP_HOST');

$settings = $_DB_SETTINGS->get();

// =====================================================================
// Language
// =====================================================================
include(PATH_LANGUAGES.'en_US.bit');
include(PATH_LANGUAGES.$settings['language'].'.bit');

Date::set_timezone($settings['timezone']);

Date::set_locale($settings['locale']);

$translit_enable = isset($_LANG['TRANSLIT'])?$_LANG['TRANSLIT']:false;

?>

<!DOCTYPE HTML>
<html>
<head>
	<meta charset="utf-8">
	<title>Nibbleblog Updater <?php echo UPDATER_VERSION ?></title>

	<style type="text/css">
		body {
			font-family: arial,sans-serif;
			background-color: #FFF;
			margin: 0;
			padding: 0;
			font-size: 0.875em;
			color: #555;
		}

		#container {
			background: none repeat scroll 0 0 #F9F9F9;
			border: 1px solid #EBEBEB;
			border-radius: 3px 3px 3px 3px;
			margin: 50px auto;
			max-width: 700px;
			padding: 20px 30px;
			width: 60%;
			box-shadow: 0 0 4px rgba(0, 0, 0, 0.05);
		}

		h1 {
			margin: 0 0 20px 0;
			text-align: center;
		}

		h2 {
			color: #6C7479;
		}

		a {
			color: #2361D3;
			cursor: pointer;
			text-decoration: none;
		}

		a:hover {
			text-decoration: underline;
		}

		footer {
			margin: 30px 0;
			border-top: 1px solid #f1f1f1;
			text-align: center;
			font-size: 0.9em;
		}

		#head {
			margin-bottom: 20px;
		}
</style>

</head>
<body>

	<div id="container">

		<header id="head">
			<?php
				echo Html::h1( array('content'=>$_LANG['WELCOME_TO_NIBBLEBLOG']) );
			?>
		</header>

		<noscript>
		<section id="javascript_fail">
			<h2>Javascript</h2>
			<p><?php echo $_LANG['PLEASE_ENABLE_JAVASCRIPT_IN_YOUR_BROWSER'] ?></p>
		</section>
		</noscript>

		<section id="configuration">
			<?php

				function add_if_not($obj, $name, $value)
				{
					if(!$obj->is_set($name))
					{
						$obj->addChild($name, $value);
					}
				}

				$posts_files = Filesystem::ls(PATH_POSTS, '*', 'xml', false, false, false);

				foreach($posts_files as $file_old)
				{
					$explode = explode('.', $file_old);

					if(count($explode)==11)
					{
						$post = new NBXML(PATH_POSTS.$file_old, 0, TRUE, '', FALSE);

						$unixstamp = (int)$post->getChild('pub_date');
						//$mod = (int)$post->getChild('mod_date');
						//if( ($mod!=0) && (!empty($mod)) )
							//$time_unix_2038 = 2147483647 - $mod;

						array_unshift($explode, $unixstamp);

						// Implode the filename
						$filename = implode('.', $explode);

						// Delete the old post
						unlink(PATH_POSTS.$file_old);

						// Save the new post
						$post->asXml(PATH_POSTS.$filename);

						echo Html::p( array('class'=>'pass', 'content'=>'File renamed: '.$file_old.' => '.$filename) );
					}
				}

				// notifications.xml
				if(!file_exists(FILE_XML_NOTIFICATIONS))
				{
					$xml  = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
					$xml .= '<notifications>';
					$xml .= '</notifications>';
					$obj = new NBXML($xml, 0, FALSE, '', FALSE);
					$obj->asXml( FILE_XML_NOTIFICATIONS );

					echo Html::p( array('class'=>'pass', 'content'=>'File created: '.FILE_XML_NOTIFICATIONS) );
				}

				// users.xml
				if(!file_exists(FILE_XML_USERS))
				{
					require(FILE_SHADOW);

					$xml  = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
					$xml .= '<users>';
					$xml .= '</users>';
					$obj = new NBXML($xml, 0, FALSE, '', FALSE);
					$node = $obj->addChild('user', '');
					$node->addAttribute('username', $_USER[0]["username"]);
					$node->addChild('id', 0);
					$node->addChild('session_fail_count', 0);
					$node->addChild('session_date', 0);
					$obj->asXml( FILE_XML_USERS );

					echo Html::p( array('class'=>'pass', 'content'=>'File created: '.FILE_XML_USERS) );
				}

				// tags.xml
				if(!file_exists(FILE_XML_TAGS))
				{
					$xml  = '<?xml version="1.0" encoding="utf-8" standalone="yes"?>';
					$xml .= '<tags autoinc="0">';
					$xml .= '<list></list>';
					$xml .= '<links></links>';
					$xml .= '</tags>';
					$obj = new NBXML($xml, 0, FALSE, '', FALSE);
					$obj->asXml( FILE_XML_TAGS );

					echo Html::p( array('class'=>'pass', 'content'=>'File created: '.FILE_XML_TAGS) );
				}

				// config.xml
				$obj = new NBXML(FILE_XML_CONFIG, 0, TRUE, '', FALSE);
				add_if_not($obj,'notification_comments',0);
				add_if_not($obj,'notification_session_fail',0);
				add_if_not($obj,'notification_session_start',0);
				add_if_not($obj,'notification_email_to','');
				add_if_not($obj,'notification_email_from','noreply@'.$blog_domain);

				// SEO Options
				add_if_not($obj,'seo_site_title','');
				add_if_not($obj,'seo_site_description','');
				add_if_not($obj,'seo_keywords','');
				add_if_not($obj,'seo_robots','');
				add_if_not($obj,'seo_google_code','');
				add_if_not($obj,'seo_bing_code','');
				add_if_not($obj,'seo_author','');

				if($obj->asXml( FILE_XML_CONFIG ))
					echo Html::p( array('class'=>'pass', 'content'=>'DB updated: '.FILE_XML_CONFIG) );
				else
					echo Html::p( array('class'=>'pass', 'content'=>'FAIL - DB updated: '.FILE_XML_CONFIG) );

				// comments.xml
				$obj = new NBXML(FILE_XML_COMMENTS, 0, TRUE, '', FALSE);
				add_if_not($obj,'moderate',1);
				add_if_not($obj,'sanitize',1);
				add_if_not($obj,'monitor_enable',0);
				add_if_not($obj,'monitor_api_key','');
				add_if_not($obj,'monitor_spam_control','0.75');
				add_if_not($obj,'monitor_auto_delete',0);
				$obj->asXml( FILE_XML_COMMENTS );
				echo Html::p( array('class'=>'pass', 'content'=>'DB updated: '.FILE_XML_COMMENTS) );

				// Categories
				$obj = new NBXML(FILE_XML_CATEGORIES, 0, TRUE, '', FALSE);

				foreach( $obj->children() as $children )
				{
					$name = utf8_decode((string)$children->attributes()->name);

					$slug = Text::clean_url($name, '-', $translit_enable);

					@$children->addAttribute('slug','');

					$children->attributes()->slug = utf8_encode($slug);
				}

				$obj->asXml( FILE_XML_CATEGORIES );

				echo Html::p( array('class'=>'pass', 'content'=>'Categories updated...') );

			?>
		</section>

		<footer>
			<p><a href="http://nibbleblog.com">Nibbleblog <?php echo NIBBLEBLOG_VERSION ?> "<?php echo NIBBLEBLOG_NAME ?>"</a> | Copyright (2009 - 2013) + GPL v3 | Developed by Diego Najar </p>
		</footer>

	</div>

</body>
</html>