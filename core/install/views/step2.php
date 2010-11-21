<? $this->render('form_top'); ?>

<div class="title">
	Install Escher Step 2 of 4: Initialize Database
</div>

<div class="installer">

	<div class="banner">
		<p>
			Please enter configuration information for your database.
		</p>
	</div>

	<form method="post" action="">
		<fieldset>
			<div class="form-area">
				<div class="field">
					<label for="selected_driver">Database Kind:</label>
					<select name="selected_driver" onchange="window.location='<?= $page_base_url ?>' + '/' + this.value">
					<? foreach($db_drivers as $id=>$name): ?>
						<option value="<?= $id ?>" <?= ($id == $selected_driver) ? 'selected="selected"' : '' ?>><?= $this->escape($name) ?></option>
					<? endforeach; ?>
					</select>
					<?= isset($errors['selected_driver']) ? "<div class=\"error\">{$this->escape($errors['selected_driver'])}</div>" : '' ?>
				</div>
<? $this->render('setup_database'); ?>
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
