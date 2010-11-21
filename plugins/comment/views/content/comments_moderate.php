<? $this->render('form_top'); ?>

<div class="title">
	Moderate Comment
</div>

<form method="post" action="">
	<input type="hidden" name="comment_id" value="<?= @$comment->id ?>" />
	<fieldset>
		<div class="form-area">

			<div class="field">
				<label class="title" for="comment_date"><a href="">Date</a></label>
				<div>
					<input class="textbox" id="comment_date" maxlength="255" name="comment_date" size="255" type="text" disabled="disabled" value="<?= $this->app->format_date($comment->time . ' UTC') ?>" />
				</div>
			</div>

			<div class="field">
				<label class="title" for="comment_author"><a href="">Author</a></label>
				<div>
					<input class="textbox" id="comment_author" maxlength="255" name="comment_author" size="255" type="text" disabled="disabled" value="<?= $this->escape($comment->author) ?>" />
				</div>
			</div>

			<div class="field">
				<label class="title" for="comment_email"><a href="">Email</a></label>
				<div>
					<input class="textbox" id="comment_email" maxlength="255" name="comment_email" size="255" type="text" disabled="disabled" value="<?= $this->escape($comment->email) ?>" />
				</div>
			</div>

			<div class="field">
				<label class="title" for="comment_web"><a href="">Web Site</a></label>
				<div>
					<input class="textbox" id="comment_web" maxlength="255" name="comment_web" size="255" type="text" disabled="disabled" value="<?= $this->escape($comment->web) ?>" />
				</div>
			</div>

			<div class="field">
				<label class="title<?= isset($errors['comment_message']) ? ' error' : '' ?>" for="comment_message"><a href="">Body</a></label>
				<div>
					<textarea id="comment_message" class="code" name="comment_message" rows="3" cols="80"<?= $can_edit ? '' : 'disabled="disabled"' ?>><?= $this->escape($comment->message) ?></textarea>
					<?= isset($errors['comment_message']) ? "<div class=\"error\">{$this->escape($errors['comment_message'])}</div>" : '' ?>
				</div>
			</div>

			<div class="field">
				<label class="title" for="comment_approved">Approved</label>
				<select <?= !$can_approve ? 'disabled="disabled "' : '' ?>id="comment_approved" name="comment_approved">
					<option value="0"<?= ($comment->approved == 0) ? ' selected="selected"' : '' ?>>No</option>
					<option value="1"<?= ($comment->approved == 1) ? ' selected="selected"' : '' ?>>Yes</option>
				</select>
			</div>

		</div>
	</fieldset>

	<div class="buttons">
<? if ($showButtons = ($can_save || $can_delete)): ?>
<? if ($can_save): ?>
		<button class="positive" type="submit" name="save">
			<img src="<?= $image_root.'tick.png' ?>" alt="" />
			Save Comment
		</button>
<? endif; ?>
<? if ($can_delete): ?>
		<a class="negative" href="<?= $this->urlTo('/content/comments/delete/'.$comment->id) ?>">
			<img src="<?= $image_root.'cross.png' ?>" alt="" />
			Delete Comment
		</a>
<? endif; ?>
<? endif; ?>
	</div>
	<?= $showButtons ? 'or' : '' ?> <a href="<?= $this->urlTo('/content/comments') ?>">Cancel</a>
</form>

<div class="clear"></div>
