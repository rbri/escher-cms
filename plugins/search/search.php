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

class _Search extends EscherPlugin
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
		return $this->app->get_pref('search_enabled');
	}
	
	//---------------------------------------------------------------------------
	
	public function install()
	{
		$this->installSnippets();
		$this->installPrefs();
	}
	
	//---------------------------------------------------------------------------
	
	private function installSnippets()
	{
		$adminModel = $this->newModel('AdminContent');
		$userID = $this->app->get_user()->id;

		$content = <<<EOD
<et:form:open id="search" nonce="0" action='<et:design:param name="action" default=''<et:find url="/search"><et:url /></et:find>'' />'>
	<p>
		<label for="search-textbox">Search</label>
		<et:form:text id="search-textbox" type="search" name="query" default='<et:query:var name="query" get="true" post="true" />' placeholder="Search My Site" /> <et:form:submit value="Go"/>
	</p>
</et:form:open>
EOD;

		$adminModel->addSnippet
		(
			$this->factory->manufacture
			(
				'Snippet', array
				(
					'name'=>'default-search-form',
					'content'=>$content,
					'author_id'=>$userID,
					'theme_id'=>0,
					'branch'=>1,
				)
			)
		);

		$content = <<<EOD
<et:ns:search>
	<et:iteration start='<et:pagination:page_num />' limit='<et:design:param name="limit" default="0" />' status='<et:design:param name="status" default="published,sticky" />'>
		<et:if_found find='<et:query:var name="query" get="true" post="true" />' mode='<et:design:param name="mode" default="" />' min='<et:design:param name="min" default="1" />' max='<et:design:param name="max" default="0" />' parent='<et:design:param name="parent" default="0" />' parts='<et:design:param name="parts" default="body" />'>
			<div class="search-results">
				<p><et:anchor rel="bookmark" qs='query=<et:search:term />'>Bookmark This Search</et:anchor></p>
				<et:each>
					<h3><et:pages:anchor /></h3>
					<p><et:date /></p>
					<p><et:excerpts_each><et:excerpt /></et:excerpts_each></p>
					<p><et:anchor rel="bookmark"><et:url /></et:anchor></p>
				</et:each>
				<div class="divider">&nbsp;</div>
			</div>
			<p>Showing <et:count /> of <et:count start="1" limit="0" /> total results.</p>
			<div id="page-navigation-links">
				<et:if_any_after><et:pagination:next_link qsa="query">&larr; <span>Previous results</span></et:pagination:next_link></et:if_any_after>
				<et:if_any_before><et:pagination:prev_link qsa="query"><span>Next results</span> &rarr;</et:pagination:prev_link></et:if_any_before>
			</div>
		<et:else />
			<div class="no-search-results">
				<p>Sorry, no results matched your search.</p>
			</div>
		</et:if_found>
	</et:iteration>
</et:ns:search>
EOD;

		$adminModel->addSnippet
		(
			$this->factory->manufacture
			(
				'Snippet', array
				(
					'name'=>'default-search-results',
					'content'=>$content,
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
				'name' => 'search_enabled',
				'group_name' => 'plugins',
				'section_name' => 'search',
				'position' => 0,
				'type' => 'hidden',
				'val' => true,
			),
		));
	}
	
	//---------------------------------------------------------------------------
}
