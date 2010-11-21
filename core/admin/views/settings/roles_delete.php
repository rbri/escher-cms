<? $this->render('form_top'); ?>

<div class="title">
	Delete Role
</div>

<? $role_name = $this->escape($role_name); ?>

<div id="page-header">
	<ul>
		<li class="warning">Are you sure you want to permanently delete role &ldquo;<?= $role_name ?>?&rdquo;</li>
	</ul>
</div>

<form method="post" action="">
	<input type="hidden" name="role_id" value="<?= @$role_id ?>" />
	&nbsp;
	<div class="buttons">
		<button class="negative" type="submit" name="delete">
			<img src="<?= $image_root.'cross.png' ?>" alt="" />
			Delete Role &ldquo;<?= $role_name ?>&rdquo;
		</button>
	</div>
	or <a href="<?= $this->urlTo($can_edit ? ('/settings/roles/edit/'.$role_id) : '/settings/roles') ?>">Cancel</a>
</form>
<div class="clear"></div>
