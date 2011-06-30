<?php

/*
Copyright 2009-2011 Sam Weiss
All Rights Reserved.

This file is part of Escher.

Escher is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

if (!defined('escher'))
{
	header('HTTP/1.1 403 Forbidden');
	exit('<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN"><html><head><title>403 Forbidden</title></head><body><h1>Forbidden</h1><p>You don\'t have permission to access the requested resource on this server.</p></body></html>');
}

require(escher_core_dir.'/shared/escher_parser.php');

class _PublishController extends SparkController
{
	private $_content;
	private $_theme;
	
	//---------------------------------------------------------------------------

	// Public Methods
	
	//---------------------------------------------------------------------------

	public function action_index($params)
	{
		$params['env'] = array();

		$prefsModel = $this->app->get_prefs_model();
		$prefs =& $this->app->get_prefs();

		$params['prefs'] =& $prefs;
		$params['category_slug'] = @$prefs['category_slug'];
				
		switch ($params['production_status'] = $this->app->get_production_status())
		{
			case EscherProductionStatus::Staging:
				$cacheSuffix = '.staging';
				$partialCachePref = 'partial_cache_flush_staging';
				$params['drafts_are_published'] = @$prefs['staging_draft_as_published'] ? true : false;
				$params['debug_level'] = @$prefs['staging_debug_level'];
				$themeID = @$prefs['staging_theme'];
				break;
			case EscherProductionStatus::Development:
				$cacheSuffix = '.dev';
				$partialCachePref = 'partial_cache_flush_dev';
				$params['drafts_are_published'] = @$prefs['development_draft_as_published'] ? true : false;
				$params['debug_level'] = @$prefs['development_debug_level'];
				$themeID = @$prefs['development_theme'];
				break;
			default:
				$cacheSuffix = '';
				$partialCachePref = 'partial_cache_flush';
				$params['drafts_are_published'] = false;
				$params['debug_level'] = @$prefs['debug_level'];
				$themeID = @$prefs['theme'];
				break;
		}
		
		if (!empty($cacheSuffix))
		{
			if ($plugCacheDir = $this->factory->getPlugCacheDir())
			{
				$plugCacheDir = rtrim($plugCacheDir, '/\\') . $cacheSuffix;
			}
		}
		else
		{
			$plugCacheDir = NULL;
		}

		$hostPrefix = $this->app->get_branch_prefix();
		if (empty($hostPrefix))
		{
			$params['site_host'] = SparkUtil::extract_scheme_host_from_url($prefs['site_url']);
			$params['secure_site_host'] = SparkUtil::extract_scheme_host_from_url($prefs['secure_site_url']);
		}
		else
		{
			$params['site_host'] = preg_replace('#^https?://#', '$0'.$hostPrefix.'.', SparkUtil::extract_scheme_host_from_url($prefs['site_url']));
			$params['secure_site_host'] = preg_replace('#^https?://#', '$0'.$hostPrefix.'.', SparkUtil::extract_scheme_host_from_url($prefs['secure_site_url']));
		}

		$this->_content = $this->newModel('PublishContent', $params);
		$this->_theme = $params['theme'] = !empty($themeID) ? $this->_content->fetchTheme(intval($themeID)) : NULL;

		// if the schema needs to be updated or an administrator has placed the site into maintenance mode,
		// display the maintenance page and exit (no plugins, user tags or caching available)

		if ($params['production_status'] == EscherProductionStatus::Maintenance)
		{
			if (($maintenancePage = $prefs['site_maintenance_page']) != '')
			{
				header('Location: ' . $maintenancePage);
				exit;
			}
			$parser = $this->factory->manufacture('EscherParser', $params, NULL, $this->_content, '/');
			$content = $parser->errorPageTemplateContent('503', 'Under Maintenance', $contentType);
			$this->display($content, $contentType);
			return;
		}

		if ($params['debug_level'] >= 9)
		{
			error_reporting(E_ALL|E_STRICT);
			ini_set('display_errors', '1');
		}
		
		// set up the cacher

		$cacher = NULL;
		if (($prefs['partial_cache_active']) && method_exists($this, 'loadCacher'))
		{
			if ($cache_params = $this->config->get('cache'))
			{
				$cache_params['namespace'] = @$cache_params['namespace'] . $cacheSuffix;
				if (isset($prefs['partial_cache_ttl']))
				{
					$cache_params['lifetime'] = $prefs['partial_cache_ttl'];
				}
				$cache_params['connection'] = $this->_content->loadDBWithPerm(EscherModel::PermWrite);
				$cacher = $this->loadCacher($cache_params);

				if (!empty($prefs[$partialCachePref]))
				{
					$cacher->clear();
					$changedPrefs[$partialCachePref] = array('name'=>$partialCachePref, 'val'=>0);
					$prefsModel->updatePrefs($changedPrefs);
				}
			}
		}
		
		// load user tags
		
		$this->factory->addPlug(array('name'=>'EscherParserUserTags', 'extends'=>'EscherParser', 'order'=>100, 'callback'=>array($this, 'buildParserPlug')));
		
		$uri = '/' . implode('/', $params['segments']);
		try
		{
			if ($plugCacheDir)
			{
				$saveDir = $this->factory->setPlugCacheDir($plugCacheDir);	// ensure user tags are cached separately for each branch
			}
			$parser = $this->factory->manufacture('EscherParser', $params, $cacher, $this->_content, $uri);
			if ($plugCacheDir)
			{
				$this->factory->setPlugCacheDir($saveDir);
			}
			$content = $parser->currentPageTemplateContent($contentType, $parsable, $cacheable, $secure, $lastModTime, $fileName, $fileSize);

			// sanity check host name in url
			
			$schemeHost = SparkUtil::scheme().SparkUtil::host();
			$expectHost = NULL;
			
			if (!$secure)
			{
				$checkFailed = ($schemeHost !== ($expectHost = $params['site_host']));
			}
			elseif ($prefs['enforce_page_security'])
			{
				$checkFailed = ($schemeHost !== ($expectHost = $params['secure_site_host']));
			}
			else
			{
				$checkFailed = ($schemeHost !== $params['site_host']) && ($schemeHost !== $params['secure_site_host']);
			}
			
			if ($checkFailed)
			{
				if ($expectHost && $prefs['automatic_redirect'])
				{
					header('Location: ' . $this->urlTo($uri, $expectHost, false));
					exit;
				}
				else
				{
					throw new SparkHTTPException_NotFound(NULL, array('reason'=>'host scheme mismatch'));
				}
			}
		}
		catch (SparkHTTPException $e)
		{
			$this->app->setCacheable(false);		// don't cache error pages!
			if ($plugCacheDir)
			{
				$saveDir = $this->factory->setPlugCacheDir($plugCacheDir);	// ensure user tags are cached separately for each branch
			}
			$parser = $this->factory->manufacture('EscherParser', $params, $cacher, $this->_content, '/');
			if ($plugCacheDir)
			{
				$this->factory->setPlugCacheDir($saveDir);
			}
			$content = $parser->errorPageTemplateContent($e->getHTTPStatusCode(), $e->getMessage(), $contentType);
			$this->display($content, $contentType);
			return;
		}

		// Enable page caching for this page if page cache is globally enabled.
		// NOTE: the page cacher will automatically refuse to cache any request method other than GET.
	
		if ($cacheable && $prefs['page_cache_active'])
		{
			$this->app->setCacheable(true, intval($prefs['page_cache_ttl']), $lastModTime);
		}

		if (!empty($fileName))
		{
			$this->download($content, $contentType, $fileName, $fileSize);
		}
		else
		{
			try
			{
				$this->display($parsable ? $parser->parse($content) : $content, $contentType);
			}
			catch (SparkHTTPException $e)
			{
				$this->app->setCacheable(false);		// don't cache error pages!
				if ($plugCacheDir)
				{
					$saveDir = $this->factory->setPlugCacheDir($plugCacheDir);	// ensure user tags are cached separately for each branch
				}
				$parser = $this->factory->manufacture('EscherParser', $params, $cacher, $this->_content, '/');
				if ($plugCacheDir)
				{
					$this->factory->setPlugCacheDir($saveDir);
				}
				$content = $parser->errorPageTemplateContent($e->getHTTPStatusCode(), $e->getMessage(), $contentType);
				$this->display($content, $contentType);
				return;
			}
		}
	}

	//---------------------------------------------------------------------------

	public function buildParserPlug(&$plug)
	{
		$tagGroups = $this->_content->fetchTags($this->_theme, $this->app->get_production_status());
		
		$plugCode = '';
		$baseClass = 'EscherParser';
		$firstClass = true;
		foreach ($tagGroups as $themeID => $tags)
		{
			$class = 'EscherParserUserTags_' . $themeID;
			if ($firstClass)
			{
				$plug['first_class'] = $class;
				$firstClass = false;
			}
			$plugCode .=<<<EOD
class EscherParserUserTags_{$themeID} extends {$baseClass}
{

EOD;
			foreach ($tags as $tag)
			{
			$tagContent = str_replace(array("::{$tag->name}", "->{$tag->name}"), array("::_tag_user_{$tag->name}", "->_tag_user_{$tag->name}"), $tag->content);
			$plugCode .=<<<EOD
protected function _tag_user_{$tag->name}(\$atts)
{
{$tagContent}
}

EOD;
			}
			
			$plugCode .=<<<EOD
}

EOD;
			$baseClass = $class;
		}
		
			$plugCode .=<<<EOD
class EscherParserUserTags extends {$baseClass}
{
}

EOD;
		
		return $plugCode;
	}
	
	//---------------------------------------------------------------------------

}
