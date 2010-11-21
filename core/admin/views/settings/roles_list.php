<? $this->render('alert'); ?>

<div class="title">
	Roles
</div>

<div id="page-header">
	<ul>
		<li class="role">Role</li>
		<li class="action">Action</li>
	</ul>
</div>
<div class="hier-list">
	<ul class="level-0">
<? if (empty($roles)): ?>
	<li class="no-entries">No Roles</li>
<? else: ?>
	<? $odd = false; foreach($roles as $id => $role): ?>
		<li id="role_<?= $id ?>">
			<div class="entry">
				<div class="column first no-children">
					<?= (!$role->isAdmin && $can_edit) ? '<a href="' . $this->urlTo("/settings/roles/edit/{$id}") . '" title="Role">' : '' ?><img alt="role-icon" class="icon" src="<?= $image_root.'role.png' ?>" title="" /><span class="title"><?= $this->escape($role->name) ?></span><?= (!$role->isAdmin && $can_edit) ? '</a>' : '' ?>
				</div>
				<div class="column action">
<? if (!$role->isAdmin && $can_delete): ?>
					<a href="<?= $this->urlTo("/settings/roles/delete/{$id}") ?>"><img title="delete role" alt="delete role" src="<?= $image_root.'minus.png' ?>" /></a>
<? endif; ?>
				</div>
			</div>
		</li>
	<? endforeach; ?>
<? endif; ?>
	</ul>
</div>

<div class="buttons">
<? if ($can_add): ?>
	<a class="positive" href="<?= $this->urlTo('/settings/roles/add') ?>">Add New Role</a>
<? endif; ?>
</div>
<div class="clear"></div>
