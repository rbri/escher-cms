<? $this->render('form_top'); ?>

<div class="title">
	Upgrade Escher
</div>

<div class="installer">

	<div class="banner">
		<p>
			Escher needs to update your database.<br /><br />
		</p>
		<p>
			Although this is generally a safe operation, we strongly recommend that you
			perform a full database backup before proceeding with this upgrade.
		</p>
	</div>
	
	<form method="post" action="">
		<div class="form-area">
		</div>
		<div class="buttons">
			<button class="positive" type="submit" name="upgrade">
				<img src="<?= $image_root.'tick.png' ?>" alt="" />
				Upgrade
			</button>
		</div>
	</form>
</div>

<div class="clear"></div>
