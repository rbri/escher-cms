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

// -----------------------------------------------------------------------------

class _Feed extends EscherPlugin
{
	//---------------------------------------------------------------------------

	public function __construct($params = NULL)
	{
		parent::__construct($params);

		if ($this->app->is_admin())
		{
			if (!$this->installed())
			{
				$this->install();
			}
		}
	}

	//---------------------------------------------------------------------------
	
	public function installed()
	{
		return $this->app->get_pref('feed_enabled');
	}
	
	//---------------------------------------------------------------------------
	
	public function install()
	{
		$this->installTemplates();
		$this->installPrefs();
	}
	
	//---------------------------------------------------------------------------
	
	private function installTemplates()
	{
		$adminModel = $this->newModel('AdminContent');
		$userID = $this->app->get_user()->id;

		$content = <<<EOD
<?xml version="1.0" encoding="utf-8"?>
<feed xmlns="http://www.w3.org/2005/Atom">
	<title><et:site_name /> Feed</title>
	<subtitle><et:site_slogan /></subtitle>
	<link rel="self" type="application/atom+xml" href='<et:url />'/>
	<updated><et:date for="edited" format="atom" /></updated>
	<id><et:feed:id /></id>
	<et:page id='<et:meta name="feed-root" default=''<et:pages:id />'' tag="false" />'><link rel="alternate" type="text/html" href='<et:url />'/>
		<et:children:each limit='<et:page><et:meta name="feed-limit" default="20" tag="false" /></et:page>' notcategory="no-feed" order="desc">
			<entry>
				<title><et:title /></title>
				<link rel="alternate" type="text/html" href='<et:url />'/>
				<id><et:feed:id /></id>
				<updated><et:date for="edited" format="atom" /></updated>
				<published><et:date for="published" format="atom" /></published>
				<author>
					<name><et:author /></name>
				</author>
				<et:if_content part="summary">
				<summary type="xhtml">
					<div xmlns="http://www.w3.org/1999/xhtml">
						<et:content part="summary" />
					</div>
				</summary>
				<et:else /><et:if_content part="body">
				<content type="xhtml">
					<div xmlns="http://www.w3.org/1999/xhtml">
						<et:content />
					</div>
				</content>
				</et:if_content></et:if_content>
			</entry>
      </et:children:each>
   </et:page>
</feed>
EOD;

		$adminModel->addTemplate
		(
			$this->factory->manufacture
			(
				'Template', array
				(
					'name'=>'atom',
					'content'=>$content,
					'ctype'=>'application/atom+xml',
					'author_id'=>$userID,
					'theme_id'=>0,
					'branch'=>1,
				)
			)
		);

		$content = <<<EOD
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0">
	<channel>
		<title><et:site_name /> Feed</title>
		<pubDate><et:date for="published" format="rss" /></pubDate>
		<description><et:site_slogan /></description>
		<et:page id='<et:meta name="feed-root" default=''<et:pages:id />'' tag="false" />'><link><et:url /></link>
			<et:children:each limit='<et:page><et:meta name="feed-limit" default="20" tag="false" /></et:page>' notcategory="no-feed" order="desc">
				<item>
					<title><et:title /></title>
					<description><et:escape_html><et:excerpt maxchars="0" /></et:escape_html></description>
					<pubDate><et:date for="published" format="rss" /></pubDate>
					<guid><et:feed:id /></guid>
					<link><et:url /></link>
				</item>
			</et:children:each>
		</et:page>
	</channel>
</rss>
EOD;

		$adminModel->addTemplate
		(
			$this->factory->manufacture
			(
				'Template', array
				(
					'name'=>'rss',
					'content'=>$content,
					'ctype'=>'application/rss+xml',
					'author_id'=>$userID,
					'theme_id'=>0,
					'branch'=>1,
				)
			)
		);
	}

	//---------------------------------------------------------------------------
	
	private function installPrefs()
	{
		$model = $this->newModel('Preferences');
		
		$model->addPrefs(array
		(
			array
			(
				'name' => 'feed_enabled',
				'group_name' => 'plugins',
				'section_name' => 'feed',
				'position' => 0,
				'type' => 'hidden',
				'val' => true,
			),
		));
	}
	
	//---------------------------------------------------------------------------
}
