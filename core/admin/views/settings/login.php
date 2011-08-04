<? $this->render('form_top'); ?>

<? if (!empty($for_help)): ?>

<div class="title">
	Password Help
</div>
<? if (!empty($success)): ?>
<p>
	We have sent an email to the address you specified. Please retrieve it and follow the instructions to reset your password.
</p>
<? else: ?>
<p>
	Please enter the email address associated with your account.
</p>
<p>
	We will send you an email with instructions for resetting your password.
</p>
<form method="post">
	<fieldset>
		<div class="login">
			<div class="field">
				<label<?= isset($errors['email']) ? ' class="error"' : '' ?> for="email">Email Address:</label>
				<input type="text" id="email" name="email" size="32" maxlength="32" value="<?= $this->escape($email) ?>" />
				<?= isset($errors['email']) ? "<div class=\"error\">{$this->escape($errors['email'])}</div>" : '' ?>
			</div>
			<div class="field">
				<button class="positive" type="submit" name="submit">
					<img src="<?= $image_root.'tick.png' ?>" alt="" />
					Submit
				</button>
			</div>
		</div>
	</fieldset>
</form>
<? endif; ?>

<? elseif (!empty($for_change)): ?>

<div class="title">
	Change Password
</div>

<p>
	To change your password, enter your account user name and your new password below.
</p>
<form method="post">
	<fieldset>
		<div class="login">
			<div class="field">
				<label<?= isset($errors['username']) ? ' class="error"' : '' ?> for="username">User Name:</label>
				<input type="text" id="username" name="username" size="32" maxlength="32" value="<?= $this->escape($username) ?>" />
				<?= isset($errors['username']) ? "<div class=\"error\">{$this->escape($errors['username'])}</div>" : '' ?>
			</div>
			<div class="field">
				<label<?= isset($errors['password']) ? ' class="error"' : '' ?> for="password">New Password:</label>
				<input type="password" id="password" name="password" size="32" maxlength="32" value="<?= $this->escape($password) ?>" />
				<?= isset($errors['password']) ? "<div class=\"error\">{$this->escape($errors['password'])}</div>" : '' ?>
			</div>
			<div class="field">
				<label<?= isset($errors['repeat_password']) ? ' class="error"' : '' ?> for="repeat_password">Repeat Password:</label>
				<input type="password" id="repeat_password" name="repeat_password" size="32" maxlength="32" value="<?= $this->escape($repeat_password) ?>" />
				<?= isset($errors['repeat_password']) ? "<div class=\"error\">{$this->escape($errors['repeat_password'])}</div>" : '' ?>
			</div>
			<div class="field">
				<button class="positive" type="submit" name="login">
					<img src="<?= $image_root.'tick.png' ?>" alt="" />
					Login
				</button>
			</div>
		</div>
	</fieldset>
</form>

<? else: ?>

<div class="title">
	Account Login
</div>
<form method="post">
	<fieldset>
		<div class="login">
			<div class="field">
				<label<?= isset($errors['username']) ? ' class="error"' : '' ?> for="username">User Name:</label>
				<input type="text" id="username" name="username" size="32" maxlength="32" value="<?= $this->escape($username) ?>" />
			</div>
			<div class="field">
				<label<?= isset($errors['password']) ? ' class="error"' : '' ?> for="password">Password:</label>
				<input type="password" id="password" name="password" size="32" maxlength="32" value="<?= $this->escape($password) ?>" />
			</div>
			<div class="field">
				<button class="positive" type="submit" name="login">
					<img src="<?= $image_root.'tick.png' ?>" alt="" />
					Login
				</button>
			</div>
			<div class="help_link" >
				<a href="<?= $this->urlTo('/settings/login/help') ?>">Forget your Password?</a>
			</div>
		</div>
	</fieldset>
</form>

<? endif; ?>
<div class="clear"></div>
