<?php
/**
 * JULib
 *
 * @package          Joomla.Site
 * @subpackage       julib
 *
 * @author           Denys Nosov, denys@joomla-ua.org
 * @copyright        2016-2018 (C) Joomla! Ukraine, https://joomla-ua.org. All rights reserved.
 * @license          GNU General Public License version 2 or later; see LICENSE.txt
 */

include_once __DIR__ . '/phpthumb/phpthumb.class.php';

/**
 * JULib library
 *
 * @since  2.0
 */
class JUImg
{
	/**
	 * @param       $url
	 * @param array $attr
	 *
	 * @return string
	 *
	 * @since  2.0
	 */
	public function Render($url, array $attr = array())
	{
		if($url !== 'cover')
		{
			$url = trim($url, '/');
			$url = trim($url);
			$url = rawurldecode($url);

			$_error = 0;
			if(preg_match('#^(http|https|ftp)://#i', $url))
			{
				$headers = @get_headers($url);
				if(strpos($headers[ 0 ], '200') === false)
				{
					$_error = 1;
				}
			}
			else
			{
				$url = JPATH_BASE . '/' . $url;
				if(!file_exists($url))
				{
					$_error = 1;
				}
			}

			$imgfile  = pathinfo($url);
			$img_name = $imgfile[ 'filename' ];
			$imgurl   = strtolower($img_name);
			$imgurl   = preg_replace('#[[:punct:]]#', '', $imgurl);
			$imgurl   = preg_replace('#[а-яёєїіА-ЯЁЄЇІ]#isu', '', $imgurl);
			$imgurl   = str_replace(array(
				' +',
				' '
			), array(
				'_',
				''
			), $imgurl);
		}
		else
		{
			$_error   = 0;
			$img_name = implode($attr);
			$imgurl   = 'cover';
		}

		$fext        = array();
		$wh          = array();
		$img_cache   = array();
		$error_image = array();

		if(!empty($attr) && is_array($attr))
		{
			foreach($attr as $whk => $whv)
			{
				if($whk === 'f')
				{
					$fext[] = $whv;
				}

				if($whk === 'w' || $whk === 'h')
				{
					$wh[] = $whv;
				}

				if($whk === 'cache')
				{
					$img_cache[] = $whv;
				}

				if($whk === 'error_image')
				{
					$error_image[] = $whv;
				}
			}
		}

		$fext      = implode($fext);
		$fext      = '.' . ($fext == '' ? 'jpg' : $fext);
		$img_cache = implode($img_cache);
		$img_cache = ($img_cache == '' ? 'cache' : $img_cache);

		if($_error === '1')
		{
			$error_image = implode($error_image);
			$url         = ($error_image == '' ? JPATH_BASE . '/libraries/julib/noimage.png' : $error_image);
		}

		$wh        = implode('x', $wh);
		$wh        = ($wh == '' ? '0' : $wh);
		$subfolder = $img_cache . '/' . $wh . '/' . strtolower(substr(md5($img_name), -1));

		$md5 = array();

		if(!empty($attr) && is_array($attr))
		{
			foreach($attr as $k => $v)
			{
				$f     = explode('_', $k);
				$k     = $f[ 0 ];
				$md5[] = $k . $v;
			}
		}

		$target = $subfolder . '/' . strtolower(substr($imgurl, 0, 150)) . '-' . md5($url . implode('.', $md5)) . $fext;

		$this->MakeDirectory($dir = JPATH_BASE . '/' . $subfolder, $mode = 0777);

		if(file_exists(JPATH_BASE . '/' . $target))
		{
			$outpute = $target;
		}
		else
		{
			$outpute = $this->Create($url, $img_cache, $target, $attr);
		}

		return $outpute;
	}

	/**
	 * @param $dir
	 * @param $mode
	 *
	 * @return bool
	 *
	 * @since  2.0
	 */
	public function MakeDirectory($dir, $mode)
	{
		if(@mkdir($dir, $mode) || is_dir($dir))
		{
			$indexfile    = $dir . '/index.html';
			$indexcontent = '<!DOCTYPE html><title></title>';

			if(!file_exists($indexfile))
			{
				$file = fopen($indexfile, 'wb');
				fwrite($file, $indexcontent);
				fclose($file);
			}

			return true;
		}

		if(!$this->MakeDirectory(dirname($dir), $mode))
		{
			return false;
		}

		return @mkdir($dir, $mode);
	}

	/**
	 * @param       $url
	 * @param       $img_cache
	 * @param       $target
	 * @param array $attr
	 *
	 * @return string
	 *
	 * @since  2.0
	 */
	public function Create($url, $img_cache, $target, array $attr = array())
	{
		$phpThumb = new JUThumbs();

		$phpThumb->resetObject();

		$phpThumb->setParameter('config_max_source_pixels', '0');
		$phpThumb->setParameter('config_temp_directory', JPATH_BASE . '/' . $img_cache . '/');
		$phpThumb->setParameter('config_cache_directory', JPATH_BASE . '/' . $img_cache . '/');

		$phpThumb->setCacheDirectory();

		$phpThumb->setParameter('config_cache_maxfiles', '0');
		$phpThumb->setParameter('config_cache_maxsize', '0');
		$phpThumb->setParameter('config_cache_maxage', '0');
		$phpThumb->setParameter('config_error_bgcolor', 'FAFAFA');
		$phpThumb->setParameter('config_error_textcolor', '770000');
		$phpThumb->setParameter('config_nohotlink_enabled', false);

		if($url === 'cover')
		{
			$cover = array();

			if(!empty($attr) && is_array($attr))
			{
				foreach($attr as $whk => $whv)
				{
					if($whk === 'cover')
					{
						$cover[] = $whv;
					}
				}
			}

			$phpThumb->setSourceFilename(JPATH_BASE . '/libraries/julib/blank.png');
			$phpThumb->setParameter('fltr', 'clr|' . implode($cover));
		}
		else
		{
			$phpThumb->setSourceFilename($url);
		}

		$phpThumb->setParameter('q', '80');
		$phpThumb->setParameter('aoe', '1');
		$phpThumb->setParameter('f', 'jpg');

		if(is_array($attr))
		{
			foreach($attr as $k => $v)
			{
				$f = explode('_', $k);
				$k = $f[ 0 ];
				$phpThumb->setParameter($k, $v);
			}
		}

		$imagemagick = '';
		if(0 === stripos(PHP_OS, 'WIN'))
		{
			$imagemagick = 'C:/ImageMagick/convert.exe';
		}

		$phpThumb->setParameter('config_imagemagick_path', $imagemagick);
		$phpThumb->setParameter('config_prefer_imagemagick', true);
		$phpThumb->setParameter('config_imagemagick_use_thumbnail', true);

		$outpute = '';
		if($phpThumb->GenerateThumbnail())
		{
			if($phpThumb->RenderToFile(JPATH_BASE . '/' . $target))
			{
				$outpute = $target;
			}

			$phpThumb->purgeTempFiles();
		}

		return $outpute;
	}
}