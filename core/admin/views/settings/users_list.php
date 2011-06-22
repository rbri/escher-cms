<? $this->render('alert'); ?>

<div class="title">
	Users
</div>

<div id="page-header">
	<ul>
		<li class="user">User</li>
		<li class="action">Action</li>
		<li class="email">Email</li>
		<li class="login">Login</li>
		<li class="roles">Roles</li>
	</ul>
</div>
<div class="hier-list">
	<ul class="level-0">
<? if (empty($users)): ?>
	<li class="no-entries">No Users</li>
<? else: ?>
	<? $odd = false; foreach($users as $id => $user): ?>
		<li id="user_<?= $id ?>">
			<div class="entry">
				<div class="column first no-children">
					<?= $can_edit ? '<a href="' . $this->urlTo("/settings/users/edit/{$id}") . '" title="User">' : '' ?><img alt="user-icon" class="icon" src="<?= $image_root.'user.png' ?>" title="" /><span class="title"><?= $this->escape($user->name) ?></span><?= $can_edit ? '</a>' : '' ?>
				</div>
				<div class="column action">
<? if (($id != 1) && $can_delete) : ?>
					<a href="<?= $this->urlTo("/settings/users/delete/{$id}") ?>"><img title="delete user" alt="delete user" src="<?= $image_root.'minus.png' ?>" /></a>
<? endif; ?>
				</div>
				<div class="column email">
					<span class="title"><?= $this->escape($user->email) ?></span>
				</div>
				<div class="column login">
					<span class="title"><?= $this->escape($user->login) ?></span>
				</div>
				<div class="column roles">
					<span class="title"><?= $this->escape(implode(', ', $user->roleNames())) ?></span>
				</div>
			</div>
		</li>
	<? endforeach; ?>
<? endif; ?>
	</ul>
</div>

<div class="buttons">
<? if ($can_add): ?>
	<a class="positive" href="<?= $this->urlTo('/settings/users/add') ?>">Add New User</a>
<? endif; ?>
</div>
<div class="clear"></div>
