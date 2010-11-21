<? $this->render('form_top'); ?>

<div class="title">
	Install Escher
</div>

<div class="installer">

	<div class="banner">
		<p>Welcome to Escher!</p>
		<p>
			This installer will guide you through the process of configuring a basic installation of
			the Escher application on this server.
		</p>
	</div>
	
<? if (!empty($problems)): ?>
	<div class="alert">
		<p>
			The installer found some problems, indicated below. You cannot continue until you have fixed them.
		</p>
	</div>
	<ul id="problem">
		<? foreach ($problems as $problem): ?>
			<li><?= $this->escape($problem) ?></li>
		<? endforeach; ?>
	</ul>

	<form method="post" action="">
		<div class="buttons">
			<button class="positive" type="submit" name="retry">
				<img src="<?= $image_root.'tick.png' ?>" alt="" />
				Retry
			</button>
		</div>
	</form>

<? else: ?>

	<form method="post" action="">
		<div class="form-area">
		</div>
		<div class="buttons">
			<button class="positive" type="submit" name="continue">
				<img src="<?= $image_root.'tick.png' ?>" alt="" />
				Continue
			</button>
		</div>
	</form>

<? endif; ?>

</div>

<div class="clear"></div>
