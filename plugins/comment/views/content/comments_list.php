<? $this->render('alert'); ?>

<div class="title">
	Manage Comments
</div>

<?= $pagination = $this->renderPagination(NULL, true); ?>
<table id="comments-list">
	<thead>
		<tr>
			<th>ID</th>
			<th>When</th>
			<th>Message</th>
			<th>Author</th>
			<th>Email</th>
			<th>Approved</th>
		</tr>
	</thead>
	<tbody>
<? if (empty($comments)): ?>
		<tr><td colspan="6"></td></tr>
		<tr><td colspan="6" style="text-align:center;"><em>No Comments</em></td></tr>
<? else: ?>
<? $odd = false; ?>
<? foreach ($comments as $comment): ?>
		<tr class="row <?= ($odd = !$odd) ? 'odd' : 'even' ?>"><td><?= $this->escape($comment->id) ?></td><td><?= $this->escape($comment->time) ?></td><td><a href="<?= $this->urlTo("/content/comments/moderate/{$comment->id}") ?>"><?= $this->escape(SparkUtil::truncate($comment->message)) ?></a></td><td><?= $this->escape($comment->author) ?></td><td><?= $this->escape($comment->email) ?></td><td><?= $comment->approved ? 'Yes' : 'No' ?></td></tr>
<? endforeach; ?>
<? endif; ?>
	</tbody>
</table>
<?= $pagination ?>

<div class="clear"></div>
