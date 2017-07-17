<?php
use \Config;

trait QAsset {

	protected static $inline_tag; // set to script or style
	protected static $prefix; // set to 'js' or 'css'

	protected static $_inited = false;
	protected static $_external = []; // collection of external assets
	protected static $_inline = []; // collection of inline assets
	protected static $_path_patterns;
	protected static $_path_replacements;

	abstract public static function _init();
	abstract protected static function render_tag($asset, $hash); // return a rendered tag

	public static function _shared_init()
	{
		if ( ! self::$_inited)
		{
			Config::load(self::$prefix, true);
			$hash_file = Config::get(self::$prefix.'.hash_file', 'asset_hash.json');
			Config::load($hash_file, self::$prefix.'.asset_hash');
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
			if ($assets = Config::get(self::$prefix.".groups.{$group}", false))
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
	public static function push($asset, $placement = 'default')
	{
		self::_push($asset, $placement, false);
	}

	public static function push_inline($asset, $placement = 'default')
	{
		self::_push($asset, $placement, true);
	}

	protected static function _push($asset, $placement, $inline){
		self::_init();
		$placement = self::get_placement($placement);
		if ( ! is_array($asset)) $asset = [$asset];
		self::place_asset($asset, $placement, true);
	}

	protected static function place_asset($assets, $placement, $inline = false)
	{
		if ($inline) $target =& self::$_inline;
		else $target =& self::$_external;

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

		if ( ! empty(self::$_external[$placement]))
		{
			$assets = self::$_external[$placement];
			if (Config::get(self::$prefix.'.remove_group_duplicates', true))
			{
				$assets = array_unique($assets);
			}

			foreach ($assets as $asset)
			{
				if ( ! empty($asset))
				{
					$path = self::path($asset);
					$output .= self::render_tag($path, self::hash($path));
				}
			}
		}

		if ( ! empty(self::$_inline[$placement]))
		{
			$output .= "<".self::$inline_tag.">\n";
			foreach (self::$_inline[$placement] as $asset)
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
				self::$prefix.
				": $placement -->\n{$output}<!-- End ".
				self::$prefix.
				": $placement -->\n";
		}

		return $output;
	}

	protected static function process_allways_load()
	{
		if ($groups = Config::get(self::$prefix.'.always_load_groups'))
		{
			foreach ($groups as $group => $assets)
			{
				self::push_group($assets, $group);
			}
		}
	}

	protected static function load_paths()
	{
		// get the paths to help render the scripts
		if ( ! isset(self::$_path_patterns))
		{
			$map_func = function($value){return "/^{$value}::/";};
			$paths = Config::get(self::$prefix.'.paths', []);
			self::$_path_patterns     = array_map($map_func, array_keys($paths));
			self::$_path_replacements = array_values($paths);
		}
	}

	protected static function path($asset)
	{
		self::load_paths();
		$resolved = preg_replace(self::$_path_patterns, self::$_path_replacements, $asset);
		return is_array($resolved) ? $resolved[0] : $resolved;
	}

	protected static function hash($file)
	{
		$hash = Config::get(self::$prefix.".asset_hash", []);

		return empty($hash[$file]) ? '' : '?'.$hash[$file];
	}

}
