<?php
class Css
{
	protected static $_inited = false;
	protected static $_css_assets = [];
	protected static $_css_raw = [];
	protected static $_path_patterns;
	protected static $_path_replacements;

	public static function _init()
	{
		if ( ! static::$_inited)
		{
			\Config::load('css', true);
			$hash_file = \Config::get('css.hash_file', 'asset_hash.json');
			\Config::load($hash_file, 'css.asset_hash');
			static::$_inited = true;
			static::process_allways_load();
		}
	}

	/**
	 * Enable a stylesheet in the stylesheet config by name
	 * @param  [string|array] $stylesheet   Name or array of names of the css groups in config/css.php
	 * @param  [string] $placement      Either 'head' or 'footer' for placement within the layout
	 * @return [bool]                   True if found/added, false if not
	 */
	public static function push_group($groups, $placement = 'default')
	{
		static::_init();
		if ( ! is_array($groups)) $groups = [$groups];
		foreach ($groups as $key => $group)
		{
			if ($stylesheets = \Config::get("css.groups.{$group}", false))
			{
				static::place_css($stylesheets, $placement);
			}
		}
	}

	/**
	 * Use a stylesheet or string of css to one of the css placements
	 * @param [string|array] $stylesheet    String or Array of urls for stylesheets to use
	 * @param string $placement         Either 'head' or 'footer' for placement within the layout
	 */
	public static function push($stylesheets, $placement = 'default')
	{
		static::_init();
		$placement = static::get_placement($placement);
		if ( ! is_array($stylesheets)) $stylesheets = [$stylesheets];
		static::place_css($stylesheets, $placement);
	}

	public static function push_inline($stylesheet, $placement = 'default')
	{
		static::_init();
		$placement = static::get_placement($placement);
		if ( ! is_array($stylesheet)) $stylesheet = [$stylesheet];
		static::place_css($stylesheet, $placement, true);
	}

	public static function render($placement = 'default')
	{
		static::_init();
		$placement = static::get_placement($placement);
		$output = '';

		if ( ! empty(static::$_css_assets[$placement]))
		{
			$stylesheets = static::$_css_assets[$placement];
			if (\Config::get('css.remove_group_duplicates', true))
			{
				$stylesheets = array_unique($stylesheets);
			}

			foreach ($stylesheets as $stylesheet)
			{
				if ( ! empty($stylesheet))
				{
					$stylesheet = static::resolve_path($stylesheet);
					$hash = static::get_hash_for_file($stylesheet);
					$output .= "<link rel=\"stylesheet\" href=\"{$stylesheet}{$hash}\">\n";
				}
			}
		}
		if ( ! empty(static::$_css_raw[$placement]))
		{
			$output .= "<style>\n";
			foreach (static::$_css_raw[$placement] as $stylesheet)
			{
				if ( ! empty($stylesheet))
				{
					$output .= "$stylesheet\n";
				}
			}
			$output .= "</style>\n";
		}

		if ( ! empty($output))
		{
			$output = "\n<!-- Start css: $placement -->\n{$output}<!-- End css: $placement -->\n";
		}

		return $output;
	}

	public static function get_placement($placement)
	{
		return ! empty($placement) && is_string($placement) ? $placement : 'default';
	}

	protected static function place_css($stylesheets, $placement, $inline = false)
	{
		if ($inline) $target =& static::$_css_raw;
		else $target =& static::$_css_assets;

		$placement = static::get_placement($placement);
		if ( ! array_key_exists($placement, $target))
		{
			$target[$placement] = [];
		}
		$target =& $target[$placement];

		if (is_array($stylesheets)) $target = array_merge($target, $stylesheets);
		elseif (is_string($stylesheets)) $target = $stylesheets;
	}

	protected static function resolve_path($stylesheet)
	{
		// get the paths to help render the styles
		if ( ! isset(static::$_path_patterns))
		{
			$paths = \Config::get('css.paths', []);
			static::$_path_patterns     = array_map(function($value){return "/^$value::/";}, array_keys($paths));
			static::$_path_replacements = array_values($paths);
		}
		$resolved = preg_replace(static::$_path_patterns, static::$_path_replacements, $stylesheet);
		return is_array($resolved) ? $resolved[0] : $resolved;
	}

	protected static function process_allways_load()
	{
		if ($css = \Config::get('css.always_load_groups'))
		{
			foreach ($css as $group => $stylesheets)
			{
				static::push_group($stylesheets, $group);
			}
		}
	}

	protected static function get_hash_for_file($file)
	{
		$defined_hashes = \Config::get("css.asset_hash", []);

		return empty($defined_hashes[$file]) ? '' : '?'.$defined_hashes[$file];
	}
}
