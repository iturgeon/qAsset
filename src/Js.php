<?
class Js
{
	protected static $_inited = false;
	protected static $_js_assets = [];
	protected static $_js_raw = [];
	protected static $_path_patterns;
	protected static $_path_replacements;

	public static function _init()
	{
		if ( ! static::$_inited)
		{
			\Config::load('js', true);
			\Config::load('asset_hash.json', true);
			static::$_inited = true;
			static::process_allways_load();
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
		static::_init();
		if ( ! is_array($groups)) $groups = [$groups];
		foreach ($groups as $key => $group)
		{
			if ($scripts = \Config::get("js.groups.{$group}", false))
			{
				static::place_script($scripts, $placement);
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
		static::_init();
		$placement = static::get_placement($placement);
		if ( ! is_array($scripts)) $scripts = [$scripts];
		static::place_script($scripts, $placement);
	}

	public static function push_inline($script, $placement = 'default')
	{
		static::_init();
		$placement = static::get_placement($placement);
		if ( ! is_array($script)) $script = [$script];
		static::place_script($script, $placement, true);
	}

	public static function render($placement = 'default')
	{
		static::_init();
		$placement = static::get_placement($placement);
		$output = '';

		if ( ! empty(static::$_js_assets[$placement]))
		{
			$scripts = static::$_js_assets[$placement];
			if (\Config::get('js.remove_group_duplicates', true))
			{
				$scripts = array_unique($scripts);
			}
			
			foreach ($scripts as $script)
			{
				if ( ! empty($script))
				{
					$script = static::resolve_path($script);
					$hash = static::get_hash_for_file($script);
					$output .= "<script type=\"text/javascript\" src=\"$script?$hash\"></script>\n";
				}
			}
		}

		if ( ! empty(static::$_js_raw[$placement]))
		{
			$output .= "<script>\n";
			foreach (static::$_js_raw[$placement] as $script)
			{
				if ( ! empty($script))
				{
					$output .= "$script\n";
				}
			}
			$output .= "</script>\n";
		}

		if ( ! empty($output))
		{
			$output = "\n<!-- Start js: $placement -->\n{$output}<!-- End js: $placement -->\n";
		}

		return $output;
	}

	public static function get_placement($placement)
	{
		return ! empty($placement) && is_string($placement) ? $placement : 'default';
	}

	protected static function place_script($scripts, $placement, $inline = false)
	{
		if ($inline) $target =& static::$_js_raw;
		else $target =& static::$_js_assets;

		$placement = static::get_placement($placement);
		if ( ! array_key_exists($placement, $target))
		{
			$target[$placement] = [];
		}
		$target =& $target[$placement];

		if (is_array($scripts)) $target = array_merge($target, $scripts);
		elseif (is_string($scripts)) $target = $scripts;
	}

	protected static function resolve_path($script)
	{
		// get the paths to help render the scripts
		if ( ! isset(static::$_path_patterns))
		{
			$paths = \Config::get('js.paths', []);
			static::$_path_patterns     = array_map(function($value){return "/$value::/";}, array_keys($paths));
			static::$_path_replacements = array_values($paths);
		}
		$resolved = preg_replace(static::$_path_patterns, static::$_path_replacements, $script);
		return is_array($resolved) ? $resolved[0] : $resolved;
	}

	protected static function process_allways_load()
	{
		if ($js = \Config::get('js.always_load_groups'))
		{
			foreach ($js as $group => $scripts)
			{
				static::push_group($scripts, $group);
			}
		}
	}

	protected static function get_hash_for_file($file)
	{
		$defined_hashes = \Config::get("asset_hash");
		foreach ($defined_hashes as $filepath => $hash)
		{
			if ($filepath == $file)
			{
				return $hash;
			}
		}

		return "";
	}
}
