<? $this->render('form_top'); ?>

<div class="title">
	Install Escher Step 3 of 4: Create Account
</div>

<div class="installer">

	<div class="banner">
		<p>
			That went well!
		</p>
		<p>
			Now create your administrator account.
			This account will have full privileges in the Escher administrative interface.
		</p>
		<p>
			You may create additional accounts later.
		</p>
	</div>
	
	<form method="post" action="">
		<fieldset>
			<div class="form-area">
				<div class="field">
					<label for="account_name">Your Name:</label>
					<input type="text" name="account_name" size="60" value="<?= $this->escape($account_name) ?>" />
					<span class="help">Required. Enter your real name here.</span>
					<?= isset($errors['account_name']) ? "<div class=\"error\">{$this->escape($errors['account_name'])}</div>" : '' ?>
				</div>
				<div class="field">
					<label for="account_email">Your Email:</label>
					<input type="text" name="account_email" size="60" value="<?= $this->escape($account_email) ?>" />
					<span class="help">Required. Enter your email address here.</span>
					<?= isset($errors['account_email']) ? "<div class=\"error\">{$this->escape($errors['account_email'])}</div>" : '' ?>
				</div>
				<div class="field">
					<label for="account_login">Your Login:</label>
					<input type="text" name="account_login" size="60" value="<?= $this->escape($account_login) ?>" />
					<span class="help">Required. Choose a login name for your account.</span>
					<?= isset($errors['account_login']) ? "<div class=\"error\">{$this->escape($errors['account_login'])}</div>" : '' ?>
				</div>
				<div class="field">
					<label for="account_password">Your Password:</label>
					<input type="password" name="account_password" size="60" value="<?= $this->escape($account_password) ?>" />
					<span class="help">Required. Choose a password for your account.</span>
					<?= isset($errors['account_password']) ? "<div class=\"error\">{$this->escape($errors['account_password'])}</div>" : '' ?>
				</div>
				<div class="field">
					<label for="account_password_again">Your Password:</label>
					<input type="password" name="account_password_again" size="60" value="<?= $this->escape($account_password_again) ?>" />
					<span class="help">Required. Re-enter your password for verification.</span>
					<?= isset($errors['account_password_again']) ? "<div class=\"error\">{$this->escape($errors['account_password_again'])}</div>" : '' ?>
				</div>
			</div>
		</fieldset>
		<div class="buttons">
			<button class="positive" type="submit" name="continue">
				<img src="<?= $image_root.'tick.png' ?>" alt="" />
				Continue
			</button>
		</div>
	</form>

</div>

<div class="clear"></div>
