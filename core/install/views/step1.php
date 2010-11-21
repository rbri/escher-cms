<? $this->render('form_top'); ?>

<div class="title">
	Install Escher Step 1 of 4: Site Configuration
</div>

<div class="installer">

	<div class="banner">
		<p>
			Please enter configuration information for your new site.
		</p>
	</div>
	
	<form method="post" action="">
		<fieldset>
			<div class="form-area">
				<div class="field">
					<label for="site_url">Site URL:</label>
					<input type="text" name="site_url" size="60" value="<?= $this->escape($site_url) ?>" />
					<span class="help">Required. Enter the web-reachable address of your new site.</span>
					<?= isset($errors['site_url']) ? "<div class=\"error\">{$this->escape($errors['site_url'])}</div>" : '' ?>
				</div>
				<div class="field">
					<label for="site_name">Site Name:</label>
					<input type="text" name="site_name" size="60" value="<?= $this->escape($site_name) ?>" />
					<span class="help">Required. Enter the name of your new site.</span>
					<?= isset($errors['site_name']) ? "<div class=\"error\">{$this->escape($errors['site_name'])}</div>" : '' ?>
				</div>
			</div>
		</fieldset>
		<div class="buttons">
			<button class="positive" type="submit" name="continue">
				<img src="<?= $image_root.'tick.png' ?>" alt="" />
				Continue
			</button>
			<button class="positive" type="submit" name="back">
				Back
			</button>
		</div>
	</form>

</div>

<div class="clear"></div>
