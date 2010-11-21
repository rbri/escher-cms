<? $this->render('form_top'); ?>

<div class="title">
	Install Escher
</div>

<div class="installer">

	<div class="banner">
		<p>
			Congratulations! Escher has been installed successfully.<br /><br />
		</p>
		<p>
			<strong>Important Reminder!</strong> Be sure to update permissions on the following
			config file so that it is no longer writable by the web server:<br /><br />
		</p>
		<p>
			<?= $config_file ?>
		</p>
	</div>
	
	<div class="form-area">
		<a href="<?= $this->urlToStatic('/') ?>">Login</a>
	</div>

</div>
