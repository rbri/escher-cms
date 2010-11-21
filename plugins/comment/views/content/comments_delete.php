<? $this->render('form_top'); ?>

<div class="title">
	Delete Comment
</div>

<div id="page-header">
	<ul>
		<li class="warning">Are you sure you want to permanently delete this comment?</li>
	</ul>
</div>

<div class="comments-delete">
	<p>
	<?= $this->escape($comment->message) ?>
	</p>
</div>

<form method="post" action="">
	<input type="hidden" name="comment_id" value="<?= @$comment->id  ?>" />
	&nbsp;
	<div class="buttons">
		<button class="negative" type="submit" name="delete">
			<img src="<?= $image_root.'cross.png' ?>" alt="" />
			Delete Comment
		</button>
	</div>
	or <a href="<?= $this->urlTo($can_moderate ? '/content/comments/moderate/'.$comment->id : '/content/comments') ?>">Cancel</a>
</form>
<div class="clear"></div>
