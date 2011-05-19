<script type="text/javascript">
	function toggleFieldEvent(event)		// override to avoid conflict
	{
	}
	$(document).ready(function(){
		$("input:checkbox.master").click(function(event) {
			checked = $(this).attr('checked');
			$(this).parent().parent().parent().parent().find("input:checkbox").each(function() {
				$(this).attr('checked', checked);
			})
		});
	});
</script>

<?php
	function outputChanges($changes, $ticks, $lang, $self)
	{

		$out = <<< EOD
<div class="field">
	<div class="inner-field">
		<fieldset>
			<ul id="change-list">

EOD;

		if (empty($changes))
		{
			$out .= <<< EOD
				<li>
					<div style="text-align:center;"><strong><em>No Changes</em></strong></div>
				</li>

EOD;
		}

		foreach ($changes as $groupID => $group)
		{
			$groupName = $self->escape(SparkInflector::humanize($groupID)) . 's';
			$out .= <<< EOD
				<li class="header">
					<span class="label"><a class="collapse">{$groupName}</a></span>
				</li>
				<li class="collapsible"><ul>
					<li>
						<table class="asset-changes">

EOD;
			if (!empty($group))
			{
				$even = false;
				$out .= <<< EOD
							<tr class="odd"><th>Name</th><th>Theme</th><th>Author</th><th>Created</th><th>Editor</th><th>Edited</th><th>Status</th><th><span class="checkbox"><input class="master" id="{$groupID}" name="{$groupID}" type="checkbox" /></span></th></tr>

EOD;
			}
			foreach ($group as $change)
			{
				$id = $change['id'];
				$name = $self->escape($change['name']);
				$theme = isset($change['theme']) ? $self->escape($change['theme']) : '';
				$author = $self->escape($change['author']);
				$created = $self->app->format_date($change['created']);
				$editor = $self->escape($change['editor']);
				$edited = $self->app->format_date($change['edited']);
				$status = $self->escape($change['status']);

				$even = !$even;
				$evenOdd = $even ? 'even' : 'odd';

				$checkBoxID = "{$groupID}-{$id}";
				$checked = !empty($ticks[$checkBoxID]) ? ' checked="checked"' : '';

				$out .= <<< EOD
			
							<tr class="even"><td>{$name}</td><td>{$theme}</td><td>{$author}</td><td>{$created}</td><td>{$editor}</td><td>{$edited}</td><td>{$status}</td><td><span class="checkbox"><input id="{$checkBoxID}" name="{$checkBoxID}" type="checkbox"{$checked} /></span></td></tr>

EOD;
			}

			$out .= <<< EOD
						</table>
					</li>
				</ul></li>
				<li class="spacer"></li>

EOD;
		}

		$out .= <<< EOD
			</ul>
		</fieldset>
	</div>
</div>

EOD;

		return $out;
	}
?>

<? $this->render('form_top'); ?>

<div class="title">
	Manage Branch
</div>

<? if (isset($branch)): ?>

<? if (empty($ticks)): ?>
<? elseif (!empty($confirm_push)): ?>
<div id="page-header">
	<ul>
		<li class="warning">
			Are you sure you want to permanently push changes to the selected assets on branch &ldquo;<?= $this->escape($branch_name) ?>?&rdquo;
			To confirm your action, please click the  &ldquo;Push Selected&rdquo; button again.
		</li>
	</ul>
</div>
<? elseif (!empty($confirm_rollback)): ?>
<div id="page-header">
	<ul>
		<li class="warning">
			Are you sure you want to permanently rollback changes to the selected assets on branch &ldquo;<?= $this->escape($branch_name) ?>?&rdquo;
			This action will revert the selected assets of this branch to the current state of its parent branch.
			All modifications to the selected assets on this branch will be lost.
			To confirm your action, please click the  &ldquo;Roll Back Selected&rdquo; button again.
		</li>
	</ul>
</div>
<? endif; ?>

<form method="post" action="">
	<input type="hidden" name="branch_id" value="<?= @$branch->id ?>" />
<? if (!empty($confirm_push)): ?>
	<input type="hidden" name="push_confirmed" value="1" />
<? elseif (!empty($confirm_rollback)): ?>
	<input type="hidden" name="rollback_confirmed" value="1" />
<? endif; ?>
	<div class="form-area">

		<div class="field">
			<label class="title<?= isset($errors['branch_name']) ? ' error' : '' ?>" for="branch_name"><a href="">Name</a></label>
			<div>
				<fieldset>
					<input class="textbox" id="branch_name" disabled="disabled" maxlength="255" name="branch_name" size="255" type="text" value="<?= $this->escape($branch->name) ?>" />
					<?= isset($errors['branch_name']) ? "<div class=\"error\">{$this->escape($errors['branch_name'])}</div>" : '' ?>
				</fieldset>
			</div>
		</div>

		<div class="field">
			<label class="title">Changes to this Branch</label>
			<div class="inner-field">
				<fieldset>
<?= outputChanges($changes, @$ticks, $lang, $this)  ?>
				</fieldset>
			</div>
		</div>

	</div>
	<div class="buttons">
<? if ($showButtons = !empty($changes) && ($can_push || $can_rollback)): ?>
<? if ($can_push): ?>
		<button class="positive" type="submit" name="push">
			<img src="<?= $image_root.'tick.png' ?>" alt="" />
			Push Selected
		</button>
<? endif; ?>
<? if ($can_rollback): ?>
		<button class="negative" type="submit" name="rollback">
			<img src="<?= $image_root.'cross.png' ?>" alt="" />
			Roll Back Selected
		</button>
<? endif; ?>
<? endif; ?>
	</div>
	<?= $showButtons ? 'or' : '' ?> <a href="<?= $this->urlTo('/design/branches') ?>">Cancel</a>
</form>
<? endif; ?>
<div class="clear"></div>
