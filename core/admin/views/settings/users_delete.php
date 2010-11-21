<? $this->render('form_top'); ?>

<div class="title">
	Delete User
</div>

<? $user_name = $this->escape($user_name); ?>

<div id="page-header">
	<ul>
		<li class="warning">Are you sure you want to permanently delete user &ldquo;<?= $user_name ?>?&rdquo;</li>
	</ul>
</div>

<form method="post" action="">
	<input type="hidden" name="user_id" value="<?= @$user_id ?>" />
	&nbsp;
	<div class="buttons">
		<button class="negative" type="submit" name="delete">
			<img src="<?= $image_root.'cross.png' ?>" alt="" />
			Delete User &ldquo;<?= $user_name ?>&rdquo;
		</button>
	</div>
	or <a href="<?= $this->urlTo($can_edit ? ('/settings/users/edit/'.$user_id) : '/settings/users') ?>">Cancel</a>
</form>
<div class="clear"></div>
