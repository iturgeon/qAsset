<?php
class Css
{
	use QAsset;

	public static function _init()
	{
		self::$inline_tag = 'style';
		self::$prefix = 'css';
		self::_shared_init();
	}

	protected static function render_tag($asset, $hash)
	{
		return "<link rel=\"stylesheet\" href=\"{$asset}{$hash}\">\n";
	}

}
