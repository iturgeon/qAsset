qAsset
==================

This library lets you manage Javascript and CSS includes in FuelPHP using configuration files like Casset, minus all the the processing features.  I switched to using pre-processors for all of this stuff, so I wanted a simpler library to manage assets.


Configuration
==================

Groups, and default groups can be configured in config files. There is a config for js (fuel/app/config/js.php), and one for css (css.php).

Here is a sample css.php config
```php
<?php
return [
	// Reduces groups with the same name down to one
	// 'remove_group_duplicates' => true, // default = true

	// define paths shortcuts like Casset does
	'paths' => [
		'gfonts' => '//fonts.googleapis.com/',
		'theme'  => '/themes/'.Config::get('theme.active', 'default').'/assets/css/',
		'assets' => '/assets/css'
	],

	// groups to always load, and where to place them
	'always_load_groups' => [
		// default placement
		'default' => [
			'main', // groups
			'fonts',
		],
		// custom footer placement (more useful for js then it would be css!)
		'footer' => [
			'angular' // group
		]
	],

	'groups' => [
		// group is in the always_load_groups above, inserted into the default placement
		'fonts' => [
			// same as: '//fonts.googleapis.com/css?family=AWESOMEFONT:400,700,900'
			'gfonts::css?family=AWESOMEFONT:400,700,900' 
		],
		// group is in the always_load_groups above, inserted into the default placement
		'main' => [
			'theme::site.css',
			'theme::normalize.min.css',
		],
		'homepage' => [
			'theme::homepage.css',
		],
	],

];
```

Usage
==================

You can prepare CSS and JS inline in your controller methods, I find this easy for quick and dirty setup.  Later I come back and move reusables to the config file.

### Home Page Controler

```php
	public function action_index()
	{
		// insert the javascript group 'homepage' (defined in js.php config) into the footer placement
		Js::add_group('homepage', 'footer');

		// adds a script directly w/o using groups, send an array of scripts if you want 
		Js::add_js('theme::homepage.min.js');

		// adds inline javascript to the footer
		Js::add_js_inline('var CONST = true;', 'footer');

		// Css works the same way as Js
		Css::add_group(['homepage', 'holiday']); // you can send arrays!
		Css::add_css(['theme::one.css', 'theme::two.css']); // more arrays here
		Css::add_css_inline('div.total-mistake{ display:none; }'); // for those last minute monkey patches

		$theme = Theme::instance();
		$theme->set_template('layouts/homepage')
			->set('title', 'Welcome to the Magic Show');

		return Response::forge($theme);
	}
```

### Home Page Layout

```php
<!DOCTYPE html>
<html>
<head>
<title><?= $title ?> | Mystical Website</title>
<!-- Renders the 'default' placement for Css and Js with no arguments -->
<?= Css::render() ?>
<?= Js::render() ?>
</head>
<body>
	<!-- YOUR PAGE HERE -->

	<!-- Render the Javascript 'footer' placement -->
	<?= Js::render('footer') ?>
</body>
</html>
```
