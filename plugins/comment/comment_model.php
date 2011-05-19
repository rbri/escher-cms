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

//------------------------------------------------------------------------------

class _CommentModel extends SparkModel
{
	//---------------------------------------------------------------------------

	public function __construct($params = NULL)
	{
		parent::__construct($params);
	}

	//---------------------------------------------------------------------------
	
	public function installed()
	{
		try
		{
			$db = $this->loadDB();
			@$db->countRows('comment');
			return true;
		}
		catch (Exception $e)
		{
			return false;
		}
	}
	
	//---------------------------------------------------------------------------
	
	public function install()
	{
		$this->installPrefs();
		$this->installPerms();
		$this->installTables();
		$this->installSnippets();
	}
	
	//---------------------------------------------------------------------------
	
	public function addComment($pageID, $message, $author, $email, $web, $approved = false)
	{
		$db = $this->loadDB();
		$now = self::now();
	
		$row = array
		(
			'page_id' => $pageID,
			'message' => $message,
			'author' => $author,
			'email' => $email,
			'web' => $web,
			'approved' => $approved ? true : false,
			'ip' => SparkUtil::remote_ip(),
			'time' => $now,
		);

		try
		{
			$db->insertRow('comment', $row);
			$row['id'] = $db->lastInsertID();
			$row['time'] = $this->app->format_date();
			return new Comment($row);
		}
		catch (Exception $e)
		{
			return false;
		}
	}

	//---------------------------------------------------------------------------
	
	public function updateComment($comment)
	{
		$db = $this->loadDB();
		
		$row = array
		(
			'message' => $comment->message,
			'approved' => $comment->approved ? true : false,
		);

		$db->updateRows('comment', $row, 'id=?', $comment->id);
	}

	//---------------------------------------------------------------------------
	
	public function approveComment($comment)
	{
		$db = $this->loadDB();
	
		$db->updateRows('comment', array('approved'=>true), 'id=?', $comment->id);
	}

	//---------------------------------------------------------------------------
	
	public function deleteCommentByID($commentID)
	{
		$db = $this->loadDB();
	
		$db->deleteRows('comment', 'id=?', $commentID);
	}

	//---------------------------------------------------------------------------
	
	public function fetchComment($id)
	{
		if (!$row = $this->loadDB()->selectRow('comment', '*', 'id=?', $id))
		{
			return false;
		}
		
		return new Comment($row);
	}

	//---------------------------------------------------------------------------
	
	public function countComments($pageID = NULL, $approved = NULL)
	{
		$where = $bind = NULL;
		
		if ($approved !== NULL)
		{
			$where[] = 'approved=?';
			$bind[] = $approved;
		}
		if ($pageID)
		{
			$where[] = 'page_id=?';
			$bind[] = $pageID;
		}
		if ($where)
		{
			$where = implode(' AND ', $where);
		}

		return $this->loadDB()->countRows('comment', $where, $bind);
	}

	//---------------------------------------------------------------------------
	
	public function fetchComments($pageID = NULL, $approved = NULL, $limit = 0, $offset = 0, $order = 'desc')
	{
		$db = $this->loadDB();
	
		$where = $bind = NULL;
		if ($approved !== NULL)
		{
			$where[] = 'approved=?';
			$bind[] = $approved;
		}
		if ($pageID)
		{
			$where[] = 'page_id=?';
			$bind[] = $pageID;
		}
		if ($where)
		{
			$where = implode(' AND ', $where);
		}
		switch (strtolower($order))
		{
			case 'desc':
				$order = 'DESC';
				break;
			default:
				$order = 'ASC';
		}
		
		$sql = $db->buildSelect('comment', '*', NULL, $where, "time {$order}, id {$order}", $limit, $offset);
		
		$comments = array();
		foreach ($db->query($sql, $bind)->rows() as $row)
		{
			$row['time'] = $this->app->format_date($row['time']);
			$comments[] = new Comment($row);
		}

		return $comments;
	}

	//---------------------------------------------------------------------------
	
	private function installPrefs()
	{
		$model = $this->newModel('Preferences');
		
		$model->addPrefs(array
		(
			array
			(
				'name' => 'comments_enabled',
				'group_name' => 'plugins',
				'section_name' => 'comments',
				'position' => 10,
				'type' => 'yesnoradio',
				'val' => false,
			),
			array
			(
				'name' => 'comments_apply_nofollow',
				'group_name' => 'plugins',
				'section_name' => 'comments',
				'position' => 20,
				'type' => 'yesnoradio',
				'val' => true,
			),
			array
			(
				'name' => 'comments_require_approval',
				'group_name' => 'plugins',
				'section_name' => 'comments',
				'position' => 30,
				'type' => 'yesnoradio',
				'val' => true,
			),
			array
			(
				'name' => 'comments_notification_email',
				'group_name' => 'plugins',
				'section_name' => 'comments',
				'position' => 40,
				'type' => 'email',
				'val' => '',
				'validation' => 'optional',
			),
		));
	}
	
	//---------------------------------------------------------------------------
	
	private function installPerms()
	{
		$userModel = $this->newModel('user');
		
		$userModel->addPerms(
			array
			(
				array
				(
					'group_name' => 'content',
					'name' => 'content:comments',
				),
					array
					(
						'group_name' => 'content',
						'name' => 'content:comments:view',
					),
					array
					(
						'group_name' => 'content',
						'name' => 'content:comments:moderate',
					),
						array
						(
							'group_name' => 'content',
							'name' => 'content:comments:moderate:approve',
						),
						array
						(
							'group_name' => 'content',
							'name' => 'content:comments:moderate:edit',
						),
					array
					(
						'group_name' => 'content',
						'name' => 'content:comments:delete',
					),
			));
	}
	
	//---------------------------------------------------------------------------
	
	private function installTables()
	{
		$db = $this->loadDB();

		$ct = $db->getFunction('create_table');
		
		$ct->table('comment');
		$ct->field('id', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger, NULL, NULL, false, iSparkDBQueryFunctionCreateTable::kFlagPrimaryKey | iSparkDBQueryFunctionCreateTable::kFlagAutoincrement);
		$ct->field('page_id', iSparkDBQueryFunctionCreateTable::kFieldTypeInteger);
		$ct->field('message', iSparkDBQueryFunctionCreateTable::kFieldTypeText);
		$ct->field('author', iSparkDBQueryFunctionCreateTable::kFieldTypeString, 255);
		$ct->field('email', iSparkDBQueryFunctionCreateTable::kFieldTypeString, 255);
		$ct->field('web', iSparkDBQueryFunctionCreateTable::kFieldTypeString, 255);
		$ct->field('approved', iSparkDBQueryFunctionCreateTable::kFieldTypeBoolean, NULL, false);
		$ct->field('ip', iSparkDBQueryFunctionCreateTable::kFieldTypeString, 15);
		$ct->field('time', iSparkDBQueryFunctionCreateTable::kFieldTypeDate);
		$ct->foreignKey('page_id', 'page', 'id', array(iSparkDBQueryFunctionCreateTable::kForeignKeyTriggerDelete=>iSparkDBQueryFunctionCreateTable::kForeignKeyActionCascade, iSparkDBQueryFunctionCreateTable::kForeignKeyTriggerUpdate=>iSparkDBQueryFunctionCreateTable::kForeignKeyActionCascade));
		$db->query($ct->compile());
	}

	//---------------------------------------------------------------------------
	
	private function installSnippets()
	{
		$adminModel = $this->newModel('AdminContent');
		$userID = $this->app->get_user()->id;

		$content = <<<EOD
<et:ns:comments>
	<et:if_comments>
		<h2>Comments</h2>
		<et:each>
			<et:design:snippet name="comment" />
		</et:each>
	</et:if_comments>
	<et:design:snippet name="comment-form" />
</et:ns:comments>	
EOD;

		$adminModel->addSnippet
		(
			$this->factory->manufacture
			(
				'Snippet', array
				(
					'name'=>'default-comments',
					'content'=>$content,
					'author_id'=>$userID,
					'theme_id'=>0,
					'branch'=>1,
				)
			)
		);
		
		$content = <<<EOD
<et:ns:comments>
	<div class="comment">
		<span class="number"><et:index />.</span>
		<span class="by_line"><et:author /> wrote:</span>
		<div class="message">
			<et:content />
		</div>
	</div>
</et:ns:comments>	
EOD;

		$adminModel->addSnippet
		(
			$this->factory->manufacture
			(
				'Snippet', array
				(
					'name'=>'default-comment',
					'content'=>$content,
					'author_id'=>$userID,
					'theme_id'=>0,
					'branch'=>1,
				)
			)
		);

		$content = <<<EOD
<et:ns:comments>
	<et:if_enabled>
		<et:ns:form>
			<et:if_open id="form_comments" error_wraptag="ul" error_breaktag="li" on_submit_do='comment-add'>
				<fieldset>
					<legend>Post a Comment</legend>
					<ol>
						<li><et:text id="author" name="author" label="Your Name" rule="required|length_max[50]" /></li>
						<li><et:email id="email" name="email" label="Your Email Address" rule="required|length_max[100]" /></li>
						<li><et:text id="web" name="web" label="Your Web Site" rule="url" /></li>
						<li><et:textarea id="message" name="message" label="Your Message" rule="required|length_max[5000]" /></li>
					</ol>
				</fieldset>
				<div class="buttons">
					<et:submit id="submit" value="Post Comment" />
				</div>
			<et:else />
				<p>Thank you, your comments have been submitted for approval.</p>
			</et:if_open>
		</et:ns:form>
	</et:if_enabled>
</et:ns:comments>	
EOD;
	
		$adminModel->addSnippet
		(
			$this->factory->manufacture
			(
				'Snippet', array
				(
					'name'=>'default-comment-form',
					'content'=>$content,
					'author_id'=>$userID,
					'theme_id'=>0,
					'branch'=>1,
				)
			)
		);

		$content = <<<EOD
<et:ns:comments>
	<et:if_add_comment author='<et:form:value name="author" />' email='<et:form:value name="email" />' web='<et:form:value name="web" />' message='<et:form:value name="message" />'>
	<et:else />
		<et:form:error name="*">We&rsquo;re sorry. There was a problem submitting your comments. Please try again later.</et:form:error>
	</et:if_add_comment>
</et:ns:comments>	
EOD;
	
		$adminModel->addSnippet
		(
			$this->factory->manufacture
			(
				'Snippet', array
				(
					'name'=>'default-comment-add',
					'content'=>$content,
					'author_id'=>$userID,
					'theme_id'=>0,
					'branch'=>1,
				)
			)
		);
	}

	//---------------------------------------------------------------------------
}

//------------------------------------------------------------------------------

class Comment extends EscherObject
{
}
