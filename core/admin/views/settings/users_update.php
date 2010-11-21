<?php
	function outputRoles($user, $roles, $self)
	{
		$out = '';
		
		foreach ($roles as $id => $role)
		{
			$name = $self->escape($role->name);
			$formName = 'role_' . $id;
			$checked = isset($user->roles[$id]) ? ' checked="checked"' : '';
			$disabled = ($user->id == 1 && $id == 1) ? ' disabled="disabled"' : '';
			
			$out .= <<<EOD
					<input{$disabled} id="{$formName}" name="{$formName}" type="checkbox"{$checked} /><label for="{$formName}">{$name}</label>

EOD;
		}
		return $out;
	}
?>

<? $this->render('form_top'); ?>

<div class="title">
	<?= $mode === 'edit' ? 'Edit' : 'Add' ?> User
</div>

<? if (isset($user)): ?>
<form method="post" action="">
	<input type="hidden" name="user_id" value="<?= @$user->id ?>" />
	<div class="form-area">

		<div class="field">
			<label class="title<?= isset($errors['user_name']) ? ' error' : '' ?>" for="user_name">Real Name</label>
			<div>
				<fieldset>
					<input class="textbox" id="user_name" maxlength="255" name="user_name" size="255" type="text" value="<?= $this->escape($user->name) ?>" />
					<?= isset($errors['user_name']) ? "<div class=\"error\">{$this->escape($errors['user_name'])}</div>" : '' ?>
				</fieldset>
			</div>
		</div>

		<div class="field">
			<label class="title<?= isset($errors['user_email']) ? ' error' : '' ?>" for="user_email">Email Address</label>
			<div>
				<fieldset>
					<input class="textbox" id="user_email" maxlength="255" name="user_email" size="255" type="text" value="<?= $this->escape($user->email) ?>" />
					<?= isset($errors['user_email']) ? "<div class=\"error\">{$this->escape($errors['user_email'])}</div>" : '' ?>
				</fieldset>
			</div>
		</div>

		<div class="field">
			<label class="title<?= isset($errors['user_login']) ? ' error' : '' ?>" for="user_login">Login Name</label>
			<div>
				<fieldset>
					<input class="textbox" id="user_login" maxlength="255" name="user_login" size="255" type="text" value="<?= $this->escape($user->login) ?>" />
					<?= isset($errors['user_login']) ? "<div class=\"error\">{$this->escape($errors['user_login'])}</div>" : '' ?>
				</fieldset>
			</div>
		</div>

		<div class="field">
			<label class="title<?= isset($errors['user_password']) ? ' error' : '' ?>" for="user_password">New Password</label>
			<div>
				<fieldset>
					<input class="textbox" id="user_password" maxlength="255" name="user_password" size="255" type="password" value="<?= $this->escape($user->password) ?>" />
<? if ($show_mail_password): ?>
					<input class="checkbox" id="mail_password" name="mail_password" type="checkbox"<?= isset($mail_password) ? ' checked="checked"' : '' ?> /><label for="mail_password">Mail password to user</label>
<? endif; ?>
					<?= isset($errors['user_password']) ? "<div class=\"error\">{$this->escape($errors['user_password'])}</div>" : '' ?>
				</fieldset>
			</div>
		</div>

		<div class="field">
			<label class="title<?= isset($errors['user_roles']) ? ' error' : '' ?>">Roles</label>
			<div id="user-roles" class="inner-field">
				<fieldset>
<?= outputRoles($user, $roles, $this) ?>
				</fieldset>
			</div>
		</div>

	</div>
	<div class="buttons">
<? if ($showButtons = ($can_save || ($mode === 'edit' && ($user->id != 1) && $can_delete))): ?>
<? if ($can_save): ?>
		<button class="positive" type="submit" name="save">
			<img src="<?= $image_root.'tick.png' ?>" alt="" />
			<?= ($mode === 'edit') ? 'Save Changes' : 'Add User' ?>
		</button>
		<button class="positive" type="submit" name="continue">
			<img src="<?= $image_root.'tick.png' ?>" alt="" />
			Save and Continue Editing
		</button>
<? endif; ?>
		<? if (($mode === 'edit') && ($user->id != 1) && $can_delete): ?>
			<a class="negative" href="<?= $this->urlTo('/settings/users/delete/'.$user->id) ?>"><img src="<?= $image_root.'cross.png' ?>" alt="" />Delete User</a>
		<? endif; ?>
<? endif; ?>
	</div>
	<?= $showButtons ? 'or' : '' ?> <a href="<?= $this->urlTo('/settings/users') ?>">Cancel</a>
</form>
<? elseif ($can_add): ?>
<div class="buttons">
	<a class="positive" href="<?= $this->urlTo('/settings/users/add') ?>">Add New User</a>
</div>
<? endif; ?>
<div class="clear"></div>
