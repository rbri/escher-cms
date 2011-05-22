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

$lang['prefs'] = array
(
	'site_url' => 'Site URL',
	'site_name' => NULL,
	'site_slogan' => NULL,
	
	'site_time_zone' => NULL,
	'site_maintenance_page' => NULL,
	'production_status' => NULL,
	'debug_level' => 'Production Debug Lebel',
	
	'category_trigger' => NULL,
	'permlink_titles' => NULL,
	
	'content_image_path' => NULL,
	'content_file_path' => NULL,
	'max_upload_size' => NULL,
	
	'theme' => NULL,
	'theme_path' => NULL,
	'style_path' => NULL,
	'script_path' => NULL,
	'image_path' => NULL,
	
	'enable_parsing_in_blocks' => NULL,
	'enable_parsing_in_parts' => NULL,
	
	'working_branch' => NULL,

	'enable_development_branch_auto_routing' => NULL,
	'development_branch_host_prefix' => NULL,
	'development_debug_level' => NULL,
	'development_theme' => NULL,
	
	'enable_staging_branch_auto_routing' => NULL,
	'staging_branch_host_prefix' => NULL,
	'staging_debug_level' => NULL,
	'staging_theme' => NULL,

	'partial_cache_active' => NULL,
	'partial_cache_ttl' => 'Partial Cache Lifetime (in seconds)',
	'page_cache_active' => NULL,
	'page_cache_ttl' => 'Page Cache Lifetime (in seconds)',
	'auto_versioned_styles' => NULL,
	'auto_versioned_scripts' => NULL,
	'auto_versioned_images' => NULL,
	'auto_versioned_files' => NULL,
	
	'require_secure_login' => NULL,
	
	'secure_site_url' => 'Secure Site URL',
	'enforce_page_security' => NULL,
	
	'automatic_redirect' => NULL,
	'last_update_version' => NULL,
	'last_update_check' => NULL,
	'check_for_updates' => NULL,


	'site_url_help' => 'The full URL to your site\'s main page, including protocol scheme and host.',
	'site_name_help' => 'The name or title of your site. You may determine where this is displayed (browser title bar, RSS feed, etc.) via the <et:site_name> tag.',
	'site_slogan_help' => 'A brief summary or description of your site to be displayed as designated by your use of the <et:site_slogan> tag.',
	
	'site_time_zone_help' => 'All dates will be displayed in the chosen time zone.',
	'site_maintenance_page_help' => 'Optionally specify a static page not managed by Escher to be displayed when site is placed into Maintenance mode. Recommended.',
	'production_status_help' => 'Determine which branch of your site is displayed to vistors to your Site URL. Live sites should operate in Production mode. Maintenance mode takes the site offline.',
	'debug_level_help' => 'Debug level for your production branch. Higher debug levels will result in progressively more information being added to the source of generated pages. Select 0 to disable all debug messages. Select 9 to display errors and warnings on pages (not recommended for live sites).',
	
	'category_trigger_help' => 'Customize the word that invokes category pages when present in the URL.',
	'permlink_titles_help' => 'Automatically append SEO-friendly page title to permlinks?',

	'content_image_path_help' => 'Path component of URL to your content images page or directory. If Escher is managing your content images, this must match where your magic page for content images resides in your page hierarchy.',
	'content_file_path_help' => 'Path component of URL to your downloadable files page or directory. If Escher is managing your downloads, this must match where your magic page for files resides in your page hierarchy.',
	'max_upload_size_help' => 'Disallow file and image uploads larger than this many bytes.',

	'theme_help' => 'Active theme for your production site.',
	'theme_path_help' => 'Path component of URL to your themes page or directory. If Escher is managing your themes, this must match where your magic page for themes resides in your page hierarchy.',
	'style_path_help' => 'Path component of URL to your styles page or directory. If Escher is managing your style sheets, this must match where your magic page for styles resides in your page hierarchy.',
	'script_path_help' => 'Path component of URL to your scripts page or directory. If Escher is managing your scripts, this must match where your magic page for scripts resides in your page hierarchy.',
	'image_path_help' => 'Path component of URL to your design images page or directory. If Escher is managing your design images, this must match where your magic page for design images resides in your page hierarchy.',

	'enable_parsing_in_blocks_help' => 'Allow use of Escher tags in block content?',
	'enable_parsing_in_parts_help' => 'Allow use of Escher tags in page parts?',
	
	'working_branch_help' => 'The branch to which changes will apply when editing your site in the admin interface. This setting does not affect which branch of your site is displayed to the browser. That is determined by your Production Status setting.',

	'enable_development_branch_auto_routing_help' => 'Automatically route requests to your development branch when development branch host prefix is detected in uri?',
	'development_branch_host_prefix_help' => 'This prefix to your site url identifies the development branch of your site. You may wish to pick a prefix that is not easily guessable, in order to hide your development branch from public view.',
	'development_debug_level_help' => 'Debug level for your development branch. Higher debug levels will result in progressively more information being added to the source of generated pages. Select 0 to disable all debug messages. Select 9 to display errors and warnings on pages.',
	'development_theme_help' => 'Active theme for your development branch.',
	
	'enable_staging_branch_auto_routing_help' => 'Automatically route requests to your staging branch when staging branch host prefix is detected in uri?',
	'staging_branch_host_prefix_help' => 'This prefix to your site url identifies the staging branch of your site. You may wish to pick a prefix that is not easily guessable, in order to hide your staging branch from public view.',
	'staging_debug_level_help' => 'Debug level for your staging branch. Higher debug levels will result in progressively more information being added to the source of generated pages. Select 0 to disable all debug messages. Select 9 to display errors and warnings on pages.',
	'staging_theme_help' => 'Active theme for your staging branch.',

	'partial_cache_active_help' => 'Enables the <et:cache> tag. If disabled, all uses of <et:cache> tag are ignored.',
	'partial_cache_ttl_help' => 'All items cached via the <et:cache> tag will be purged after this many seconds.',
	'page_cache_active_help' => 'Enables the integrated full page cache. Any pages you have designated as cacheable will be stored to the page cache for improved performance.',
	'page_cache_ttl_help' => 'Cached pages will be purged after this many seconds, allowing dynamic content to refresh.',
	'auto_versioned_styles_help' => 'When enabled, Escher will dynamically rewrite the names of your style sheets whenever they are modified. This allows you to set "far future" expiration headers on your style sheets, a web site performance best practice.',
	'auto_versioned_scripts_help' => 'When enabled, Escher will dynamically rewrite the names of your scripts whenever they are modified. This allows you to set "far future" expiration headers on your scripts, a web site performance best practice.',
	'auto_versioned_images_help' => 'When enabled, Escher will dynamically rewrite the names of your images whenever they are modified. This allows you to set "far future" expiration headers on your images, a web site performance best practice.',
	'auto_versioned_files_help' => 'When enabled, Escher will dynamically rewrite the names of your downloadable files whenever they are modified. This allows you to set "far future" expiration headers on your downloadable files, a web site performance best practice.',

	'require_secure_login_help' => 'Allow access to the login page only via SSL? Caution! If enabled, you will lock yourself out of the admin area if the secure login page is not set up properly. Be sure to test access to your secure login page before enabling this setting.',

	'secure_site_url_help' => 'If some of your site\'s pages are secure, the full URL to your secure site\'s main page, including protocol scheme and host.',
	'enforce_page_security_help' => 'Deny access to secure pages unless accessed via secure site URL?',

	'automatic_redirect_help' => 'Automatically redirect secure pages accessed via non-secure site URL to secure site URL?',
	'check_for_updates_help' => 'How often should Escher check in with the update server to look for a new version?',
);