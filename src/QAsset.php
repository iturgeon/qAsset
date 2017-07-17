<?php
trait QAsset {

	protected static $inline_tag;
	protected static $js_or_css;

	protected static $_inited = false;
	protected static $_assets = [];
	protected static $_raw = [];
	protected static $_path_patterns;
	protected static $_path_replacements;

	abstract public static function _init();
	abstract protected static function render_tag($asset, $hash);

	public static function _shared_init()
	{
		if ( ! self::$_inited)
		{
			\Config::load(self::$js_or_css, true);
			$hash_file = \Config::get(self::$js_or_css.'.hash_file', 'asset_hash.json');
			\Config::load($hash_file, self::$js_or_css.'.asset_hash');
			self::$_inited = true;
			self::process_allways_load();
		}
	}

	/**
	 * Enable a script in the script config by name
	 * @param  [string|array] $script   Name or array of names of the script groups in config/js.php
	 * @param  [string] $placement      Either 'head' or 'footer' for placement within the layout
	 * @return [bool]                   True if found/added, false if not
	 */
	public static function push_group($groups, $placement = 'default')
	{
		self::_init();
		if ( ! is_array($groups)) $groups = [$groups];
		foreach ($groups as $key => $group)
		{
			if ($assets = \Config::get(self::$js_or_css.".groups.{$group}", false))
			{
				self::place_asset($assets, $placement);
			}
		}
	}

	/**
	 * Use a script or string of javascript to one of the javascript placements
	 * @param [string|array] $script    String or Array of urls for scripts to use
	 * @param string $placement         Either 'head' or 'footer' for placement within the layout
	 */
	public static function push($scripts, $placement = 'default')
	{
		self::_init();
		$placement = self::get_placement($placement);
		if ( ! is_array($scripts)) $scripts = [$scripts];
		self::place_asset($scripts, $placement);
	}

	public static function push_inline($asset, $placement = 'default')
	{
		self::_init();
		$placement = self::get_placement($placement);
		if ( ! is_array($asset)) $asset = [$asset];
		self::place_asset($asset, $placement, true);
	}

	protected static function place_asset($assets, $placement, $inline = false)
	{
		if ($inline) $target =& self::$_raw;
		else $target =& self::$_assets;

		$placement = self::get_placement($placement);
		if ( ! array_key_exists($placement, $target))
		{
			$target[$placement] = [];
		}
		$target =& $target[$placement];

		if (is_array($assets)) $target = array_merge($target, $assets);
		elseif (is_string($assets)) $target = $assets;
	}

	public static function get_placement($placement)
	{
		return ! empty($placement) && is_string($placement) ? $placement : 'default';
	}

	public static function render($placement = 'default')
	{
		self::_init();
		$placement = self::get_placement($placement);
		$output = '';

		if ( ! empty(self::$_assets[$placement]))
		{
			$assets = self::$_assets[$placement];
			if (\Config::get(self::$js_or_css.'.remove_group_duplicates', true))
			{
				$assets = array_unique($assets);
			}

			foreach ($assets as $asset)
			{
				if ( ! empty($asset))
				{
					$asset = self::resolve_path($asset);
					$hash = self::get_hash_for_file($asset);
					$output .= self::render_tag($asset, $hash);
				}
			}
		}

		if ( ! empty(self::$_raw[$placement]))
		{
			$output .= "<".self::$inline_tag.">\n";
			foreach (self::$_raw[$placement] as $asset)
			{
				if ( ! empty($asset))
				{
					$output .= "$asset\n";
				}
			}
			$output .= "</".self::$inline_tag.">\n";
		}

		if ( ! empty($output))
		{
			$output = "\n<!-- Start ".
				self::$js_or_css.
				": $placement -->\n{$output}<!-- End ".
				self::$js_or_css.
				": $placement -->\n";
		}

		return $output;
	}

	protected static function process_allways_load()
	{
		if ($groups = \Config::get(self::$js_or_css.'.always_load_groups'))
		{
			foreach ($groups as $group => $assets)
			{
				self::push_group($assets, $group);
			}
		}
	}

	protected static function resolve_path($asset)
	{
		// get the paths to help render the scripts
		if ( ! isset(self::$_path_patterns))
		{
			$paths = \Config::get(self::$js_or_css.'.paths', []);
			self::$_path_patterns     = array_map(function($value){return "/^$value::/";}, array_keys($paths));
			self::$_path_replacements = array_values($paths);
		}
		$resolved = preg_replace(self::$_path_patterns, self::$_path_replacements, $asset);
		return is_array($resolved) ? $resolved[0] : $resolved;
	}

	protected static function get_hash_for_file($file)
	{
		$hash = \Config::get(self::$js_or_css.".asset_hash", []);

		return empty($hash[$file]) ? '' : '?'.$hash[$file];
	}

}
