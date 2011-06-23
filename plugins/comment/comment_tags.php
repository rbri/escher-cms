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

class CommentTags extends EscherParser
{
	private $_model;
	private $_comment_stack;

	//---------------------------------------------------------------------------

	public function __construct($params, $cacher, $content, $currentURI)
	{
		parent::__construct($params, $cacher, $content, $currentURI);
		$this->_model = $this->factory->manufacture('CommentModel');
		$this->_comment_stack = array();
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_ns_comments()
	{
		$this->pushNamespace('comments');
		return true;
	}
		
	protected function _xtag_ns_comments()
	{
		$this->popNamespace('comments');
	}
		
	//---------------------------------------------------------------------------
	
	protected function _tag_comments_if_enabled($atts)
	{
		return !empty($this->prefs['comments_enabled']);
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_comments_count($atts)
	{
		$curIter = $this->currentIter();
		
		extract($this->gatts(array(
			'page' => $curIter['page'],
			'limit' => $curIter['limit'],
			'start' => $curIter['start'],
		),$atts));
		
		$offset = (max(1, $start) - 1) * $limit;
		return $this->_model->countComments($page ? $page : $this->currentPageContext()->id, true, $limit, $offset);
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_comments_if_any($atts)
	{
		return ($this->_tag_comments_count($atts) > 0);
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_comments_if_any_before($atts)
	{
		$curIter = $this->currentIter();
		
		extract($this->gatts(array(
			'page' => $curIter['page'],
			'limit' => $curIter['limit'],
			'start' => $curIter['start'],
		),$atts));

		$offset = (max(1, $start) - 1) * $limit;
		$atts['limit'] = 1;
		$atts['start'] = 1;
		return ($offset > 0) && ($this->_tag_comments_count($atts) > 0);
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_comments_if_any_after($atts)
	{
		$curIter = $this->currentIter();
		
		extract($this->gatts(array(
			'page' => $curIter['page'],
			'limit' => $curIter['limit'],
			'start' => $curIter['start'],
		),$atts));

		$atts['start'] = $start + 1;
		return ($limit > 0) && ($this->_tag_comments_count($atts) > 0);
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_comments_each($atts)
	{
		$curIter = $this->currentIter();
		
		extract($this->gatts(array(
			'page' => $curIter['page'],
			'limit' => $curIter['limit'],
			'start' => $curIter['start'],
			'order' => 'asc',
		),$atts));

		$index =& $this->pushIndex(1);

		$out = '';

		$offset = (max(1, $start) - 1) * $limit;
		if ($comments = $this->_model->fetchComments($page ? $page : $this->currentPageContext()->id, true, $limit, $offset, $order))
		{
			$content = $this->getParsable();
			
			$numComments = count($comments);
			$whichComment = 0;
			foreach ($comments as $comment)
			{
				++$whichComment;
				
				$comment->isFirst = ($whichComment == 1);
				$comment->isLast = ($whichComment == $numComments);

				$this->pushComment($comment);
				$out .= $this->parseParsable($content);
				$this->popComment();
				++$index;
			}
		}
		
		$this->popIndex();

		return $out;
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_comments_content($atts)
	{
		extract($this->gatts(array(
			'escape' => true,
		),$atts));
		
		if ($out = ($comment = $this->currentComment()) ? $comment->message : '')
		{
			if ($this->truthy($escape))
			{
				$out = $this->output->escape($out);
			}
		}
		
		return $out;
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_comments_author($atts)
	{
		return ($comment = $this->currentComment()) ? $this->output->escape($comment->author) : '';
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_comments_email($atts)
	{
		return ($comment = $this->currentComment()) ? $this->output->escape($comment->email) : '';
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_comments_web($atts)
	{
		$url = '';
		
		if ($comment = $this->currentComment())
		{
			$url = $this->output->escape($comment->web);
			
			if (!empty($url))
			{
				if (stripos($url, 'http') !== 0)
				{
					$url = 'http://' . $url;
				}
				
				if ($this->hasContent() || $this->truthy(@$atts['aslink']))
				{
					unset($atts['aslink']);
					$atts['href'] = $url;
					if ($this->prefs['comments_apply_nofollow'])
					{
						$atts['rel'] = 'nofollow';
					}
					$atts = $this->matts($atts);
					$content = $this->hasContent() ? $this->getContent() : $url;
					return $this->output->tag($content, 'a', '', '', $atts);
				}
			}
		}
		
		return $url;
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_comments_date($atts)
	{
		extract($this->gatts(array(
			'format' => '%A, %B %d, %Y',
		),$atts));
		
		return ($comment = $this->currentComment()) ? $this->output->escape($this->app->format_date($comment->time . ' UTC', $format, 1)) : '';
	}
	
	//---------------------------------------------------------------------------
	
	protected function _tag_comments_if_add_comment($atts)
	{
		extract($this->gatts(array(
			'author' => '',
			'email' => '',
			'web' => '',
			'message' => '',
		),$atts));
		
		if ($comment = $this->_model->addComment($this->currentPageContext()->id, $message, $author, $email, $web, empty($this->prefs['comments_require_approval'])))
		{
			// send site changed notification
			
			$this->observer->notify('escher:site_change:content:comment:add', $comment);
			
			// send comment notification email
			
			if ($notify = $this->prefs['comments_notification_email'])
			{
				if (preg_match('#[^\./]+\.[^\.]+$#', $this->prefs['site_url'], $matches))
				{
					$siteDomain = $matches[0];
					$returnEmail = 'noreply@'.$siteDomain;
					$mailer = $this->factory->manufacture('SparkMailer');
					$mailer->isHTML(false)->sender($returnEmail)->from($returnEmail)->fromName('Escher Comments Mailer')->addAddress($notify);
					$mailer->subject('Comment Received for ' . $this->app->get_pref('site_name'));
					$mailer->body('Someone has posted a comment to your site. If your site settings require comment approval, you may want to log in and approve the comment.');
					$mailer->send();
				}
			}
			return true;
		}

		return false;
	}
	
	//---------------------------------------------------------------------------
	
	protected final function pushComment($comment)
	{
		if (!($comment instanceof Comment))
		{
			$this->reportError(self::$lang->get('not_a_comment'), E_USER_WARNING);
			return;
		}
		
		$this->_comment_stack[] = $comment;
	}
	
	//---------------------------------------------------------------------------
	
	protected final function popComment()
	{
		array_pop($this->_comment_stack);
	}
	
	//---------------------------------------------------------------------------
	
	protected final function currentComment()
	{
		return end($this->_comment_stack);
	}
	
	//---------------------------------------------------------------------------
}
