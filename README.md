## qAsset

This library lets you manage Javascript and CSS includes in FuelPHP using configuration files like [Casset](https://github.com/canton7/fuelphp-casset), minus all the the processing features.  I switched to using pre-processors for all of this stuff, so I wanted a simpler library to manage assets.


## Install

Install using Composer, if qAsset isn't uploaded to Packagist, add this repo using the `repositories` directive as shown below. 

```json
    "repositories" : [
        {
            "url":"https://github.com/iturgeon/qAsset.git",
            "type":"git"
        }
    ],
    "require": {
        "php": ">=5.4",
        "iturgeon/qasset": "1.0.0"
    },
```

## Configuration

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

	// Change the precalulated hash config file name, defaults to 'asset_hash.json'
	// Supports any fuelphp config format
	'hash_file' => 'asset_hash.json'
];
```

### Cache Busting with precalculated Hashes

qAsset allows you to load pre-calculated file hashes to append to urls for the purpose of cache busting. Many pre-processors allow you pre-calculate file hashes like `hash-assets-webpack-plugin` or `gulp-hashsum-json` while building those files, this little feature will support those hashes.

By default css and js load from `asset_hash.json`, but they can be configured to load seperate files if that suits your needs better.

That config file needs to return an associative array where the key is the file reletive to the public directory, and the value is the hash

Ex:

```json
{
	"/assets/file.js": "hash_value",
	"/assets/file2.js": "other_value"
}

```

When included in the page, these 2 files will have their hash appended for the purposes of cache busting.

```html
<script src="/assets/file.js?hash_value"></script>
<script src="/assets/file2.js?other_value"></script>
```

## Usage

You can prepare CSS and JS inline in your controller methods, I find this easy for quick and dirty setup.  Later I come back and move reusables to the config file.

### Home Page Controler

```php
	public function action_index()
	{
		// insert the javascript group 'homepage' (defined in js.php config) into the footer placement
		Js::push_group('homepage', 'footer');

		// adds a script directly w/o using groups, send an array of scripts if you want 
		Js::push('theme::homepage.min.js');

		// adds inline javascript to the footer
		Js::push_inline('var CONST = true;', 'footer');

		// Css works the same way as Js
		Css::push_group(['homepage', 'holiday']); // you can send arrays!
		Css::push(['theme::one.css', 'theme::two.css']); // more arrays here
		Css::push_inline('div.total-mistake{ display:none; }'); // for those last minute monkey patches

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
