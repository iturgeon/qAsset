<?php
class Js
{
	use QAsset;

	public static function _init()
	{
		self::$inline_tag = 'script';
		self::$js_or_css = 'js';
		self::_shared_init();
	}

	protected static function render_tag($asset, $hash)
	{
		return "<script src=\"{$asset}{$hash}\"></script>\n";
	}

}
