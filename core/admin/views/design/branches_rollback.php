<? $this->render('form_top'); ?>

<div class="title">
	Rollback Branch
</div>

<? $branch_name = $this->escape($branch_name); ?>

<div id="page-header">
	<ul>
		<li class="warning">
			Are you sure you want to permanently roll back all changes to the <em><?= $this->escape($branch_name) ?></em> branch?
			<br /><br />
			This action will revert all assets of the <em><?= $this->escape($branch_name) ?></em> branch to their current state in the <em><?= $this->escape($to_branch_name) ?></em> branch.
			All modifications to the <em><?= $this->escape($branch_name) ?></em> branch will be lost.
		</li>
	</ul>
</div>

<form method="post" action="">
	<input type="hidden" name="branch_id" value="<?= @$branch_id ?>" />
	&nbsp;
	<div class="buttons">
		<button class="negative" type="submit" name="rollback">
			<img src="<?= $image_root.'cross.png' ?>" alt="" />
			Rollback Branch &ldquo;<?= $branch_name ?>&rdquo;
		</button>
	</div>
	or <a href="<?= $this->urlTo('/design/branches') ?>">Cancel</a>
</form>
<div class="clear"></div>
