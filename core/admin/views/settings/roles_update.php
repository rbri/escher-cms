<script type="text/javascript">
	function toggleFieldEvent(event)		// override to avoid conflict
	{
	}
	$(document).ready(function(){
		$("#toggle-checks").click(function(event){
			event.preventDefault();
			$("input:checkbox").each(function(){
				$(this).prop("checked", !$(this).prop("checked"));
			})
		});
		$("input:checkbox").click(function(event) {
			if ($(this).prop("checked"))
			{
				var name = "";
				var splits = $(this).attr("name").split("_");
				for (part in splits)
				{
					name = name + splits[part];
					if (part != 0)
					{
						$("input:checkbox#"+name).prop("checked", true);
					}
					name = name + "_";
				}
			}
		});
	});
</script>

<?php
	function outputPermissions($permissions, $parent, $level, &$odd, $lang, $self)
	{
		if (!empty($parent))
		{
			$parent .= '_';
		}
		$isFirst = true;

		$out = '';
		foreach ($permissions as $name => $perms)
		{
			$name = $parent . $name;
			$displayName = $self->escape($lang->get($name));
			$formName = $self->escape('perm_' . $name);
			$odd = ($level <= 1) ? false : !$odd;
			$oddEven = $odd ? ' odd' : ' even';
			$checked = $perms['val'] ? ' checked="checked"' : '';
			
			if (($level === 1) || ($level === 0 && !$isFirst))
			{
				$out .= <<<EOD
						<li class="spacer"></li>

EOD;
			}

			if ($level === 0)
			{
				$out .= <<<EOD
						<li class="level-0">
							<span class="label"><label class="title" for="{$formName}"><a class="collapse">{$displayName}</a></label></span>
							<span class="checkbox"><input id="{$formName}" name="{$formName}" type="checkbox"{$checked} /></span>
						</li>

EOD;
			}
			elseif ($level === 1)
			{
				$toggler = count($perms) > 1 ? 'expand' : 'fixed';
				$out .= <<<EOD
						<li class="level-1">
							<span class="label"><label class="title" for="{$formName}"><a class="{$toggler}">{$displayName}</a></label></span>
							<span class="checkbox"><input id="{$formName}" name="{$formName}" type="checkbox"{$checked} /></span>
						</li>

EOD;
			}
			else
			{
				$out .= <<<EOD
						<li class="level-{$level}{$oddEven}">
							<span class="label"><label for="{$formName}">{$displayName}</label></span>
							<span class="checkbox"><input id="{$formName}" name="{$formName}" type="checkbox"{$checked} /></span>
						</li>

EOD;
			}
			if (is_array($perms))
			{
				$wrapBegin = $wrapEnd = '';

				if ($level <= 1)
				{
					$wrapClass = 'collapsible' . (($level === 1) ? ' hidden' : '');
					$wrapBegin = <<<EOD
						<li class="{$wrapClass}"><ul>
	
EOD;
					$wrapEnd = <<<EOD
						</ul></li>

EOD;
				}

				unset($perms['val']);

				if ($t = outputPermissions($perms, $name, $level+1, $odd, $lang, $self))
				{
					$out .= ($wrapBegin . $t . $wrapEnd);
				}
			}
			
			$isFirst = false;
		}
		return $out;
	}
?>

<? $this->render('form_top'); ?>

<div class="title">
	<?= $mode === 'edit' ? 'Edit' : 'Add' ?> Role
</div>

<? if (isset($role)): ?>
<form method="post" action="">
	<input type="hidden" name="role_id" value="<?= @$role->id ?>" />
	<div class="form-area">

		<div class="field">
			<label class="title<?= isset($errors['role_name']) ? ' error' : '' ?>" for="role_name"><a href="">Name</a></label>
			<div>
				<fieldset>
					<input class="textbox" id="role_name" maxlength="255" name="role_name" size="255" type="text" value="<?= $this->escape($role->name) ?>" />
					<?= isset($errors['role_name']) ? "<div class=\"error\">{$this->escape($errors['role_name'])}</div>" : '' ?>
				</fieldset>
			</div>
		</div>

		<div class="field">
			<label class="title">Permissions</label>
			<div class="inner-field">
				<fieldset>
					<ul id="permissions">
						<li class="header"><a id="toggle-checks" href="">Toggle All</a></li>
<?= outputPermissions($permissions, '', 0, $odd, $lang, $this)  ?>
					</ul>
				</fieldset>
			</div>
		</div>

	</div>
	<div class="buttons">
<? if ($showButtons = ($can_save || ($mode === 'edit' && $can_delete))): ?>
<? if ($can_save): ?>
		<button class="positive" type="submit" name="save">
			<img src="<?= $image_root.'tick.png' ?>" alt="" />
			<?= ($mode === 'edit') ? 'Save Changes' : 'Add Role' ?>
		</button>
		<button class="positive" type="submit" name="continue">
			<img src="<?= $image_root.'tick.png' ?>" alt="" />
			Save and Continue Editing
		</button>
<? endif; ?>
		<? if ($mode === 'edit' && $can_delete): ?>
			<a class="negative" href="<?= $this->urlTo('/settings/roles/delete/'.$role->id) ?>"><img src="<?= $image_root.'cross.png' ?>" alt="" />Delete Role</a>
		<? endif; ?>
<? endif; ?>
	</div>
	<?= $showButtons ? 'or' : '' ?> <a href="<?= $this->urlTo('/settings/roles') ?>">Cancel</a>
</form>
<? elseif ($can_add): ?>
<div class="buttons">
	<a class="positive" href="<?= $this->urlTo('/settings/roles/add') ?>">Add New Role</a>
</div>
<? endif; ?>
<div class="clear"></div>
