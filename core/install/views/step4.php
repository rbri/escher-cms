<? $this->render('form_top'); ?>

<div class="title">
	Install Escher Step 4 of 4: Install Default Content
</div>

<div class="installer">

	<div class="banner">
		<p>
			Just one more step!
		</p>
		<p>
			If you like, we can populate your site database with some initial content to get you started.
		</p>
		<p>
			Please choose from one of the options below.
		</p>
	</div>
	
	<form method="post" action="">
		<fieldset>
			<div class="form-area">
				<div class="field">
					<input type="radio" name="content_option" value="1" <?= $content_option == 1 ? 'checked="checked"' : '' ?> />
					<label for="content_option">The default Escher CMS theme, suitable for a personal site with blog.</label>
				</div>
				<div class="field">
					<input type="radio" name="content_option" value="2" <?= $content_option == 2 ? 'checked="checked"' : '' ?> />
					<label for="content_option">The Escher CMS demo site to explore.</label>
				</div>
				<div class="field">
					<input type="radio" name="content_option" value="3" <?= $content_option == 3 ? 'checked="checked"' : '' ?> />
					<label for="content_option">A simple welcome page.</label>
				</div>
				<div class="field">
					<input type="radio" name="content_option" value="4" <?= $content_option == 4 ? 'checked="checked"' : '' ?> />
					<label for="content_option">No thanks! Just an empty site, please.</label>
				</div>
			</div>
		</fieldset>
		<div class="buttons">
			<button class="positive" type="submit" name="continue">
				<img src="<?= $image_root.'tick.png' ?>" alt="" />
				Finish!
			</button>
		</div>
	</form>

</div>

<div class="clear"></div>
