<? if (!empty($toolbar) && ($can_add_meta || $can_delete_meta)): ?>
<script type="text/javascript">
<? if ($can_delete_meta): ?>
	function removeMeta(event)
	{
		event.preventDefault();
		var meta_num = $(this).attr("name");
		if (confirm('Are you sure you want to delete metatdata "' + meta_num + '"?'))
		{
			$(this).parent().remove();
		}
	}
<? endif; ?>
	$(document).ready(function() {
<? if ($can_add_meta): ?>
		$("a.meta_close_link").click(function(event) {
			event.preventDefault();
			var popup = $("#add-meta-popup");
			center(popup);
			popup.toggle();
			$("#meta_name_input").focus();
		});

		$("form#add-meta-form").submit(function(event) {
			event.preventDefault();
			$("#add-meta-popup").hide();
			var meta_name = htmlspecialchars($("#meta_name_input").attr("value").trim().toLowerCase().replace(/\s+/g, "_"));
			$("#meta_name_input").attr("value", "");
			if (meta_name == "")
			{
				return;
			}
			var meta_name_id = "meta_" + meta_name;
			var meta_field_id = meta_name_id + "_field";
			$("div#meta fieldset").append('\
						<p id=' + meta_field_id +'>\
							<label for="'+ meta_name_id + '">' + meta_name + '</label>\
							<a class="meta_delete_link" name="' + meta_name + '" href="" title="Delete Meta"><img alt="minus" src="<?= $image_root.'minus.png' ?>" /></a>\
							<input class="textbox" id="' + meta_name_id + '" maxlength="255" name="' + meta_name_id + '" size="255" type="text" value="" />\
						</p>\
			');
			$("#"+meta_field_id+" a.meta_delete_link").click(removeMeta)
			$("#"+meta_name_id).focus();
		});
<? endif; ?>
<? if ($can_delete_meta): ?>
		$("a.meta_delete_link").click(removeMeta);
<? endif; ?>
	});
</script>
<? endif; ?>
<?
if (!empty($collapsed) && !empty($metadata))
{
	foreach($metadata as $prefix => $fields)
	{
		foreach($fields as $name => $data)
		{
			if (isset($errors[$prefix.'_'.$name]))
			{
				$collapsed = false;
				break 2;
			}
		}
	}
}
?>
<div class="field">
	<span class="title"><a class="<?= !empty($collapsed) ? 'expand' : 'collapse' ?>" href=""><?= !empty($title) ? $this->escape($title) : 'Metadata' ?></a></span>
	<div id="<?= !empty($meta_id) ? $meta_id : 'meta' ?>" class="meta collapsible persistent<?= !empty($collapsed) ? ' hidden' : '' ?>">
		<fieldset>
<? if (!empty($id)): ?>
			<p>
				<label>ID</label>
<? if (!empty($toolbar)): ?>
				<span class="spacer">&nbsp;</span>
<? endif; ?>
				<input class="textbox" disabled="disabled" type="text" value="<?= $this->escape($id) ?>" />
			</p>
<? endif; ?>
<? if (!empty($metadata)): ?>
<? foreach($metadata as $prefix => $fields): ?>
<? foreach($fields as $name => $data): ?>
			<p>
				<label<?= isset($errors[$prefix.'_'.$name]) ? ' class="error"' : '' ?> for="<?= $prefix.'_'.$name ?>"><?= isset($titles[$name]) ? $titles[$name] : $this->escape($name) ?></label>
<? if (!empty($toolbar)): ?>
<? if (!$can_delete_meta || (isset($protected) && is_array($protected) && in_array($name, $protected))): ?>
				<span class="spacer">&nbsp;</span>
<? else: ?>
				<a class="meta_delete_link" name="<?= $name ?>" href="" title="Delete Meta"><img alt="minus" src="<?= $image_root.'minus.png' ?>" /></a>
<? endif; ?>
<? endif; ?>
<? if ($can_edit_meta && (!isset($disabled) || (is_array($disabled) && !in_array($name, $disabled)))): ?>
				<input class="textbox" id="<?= $prefix.'_'.$name ?>" maxlength="255" name="<?= $prefix.'_'.$name ?>" size="255" type="text" value="<?= $this->escape($data) ?>" />
				<?= isset($errors[$prefix.'_'.$name]) ? "<div class=\"clear error\">{$this->escape($errors[$prefix.'_'.$name])}</div>" : '' ?>
<? else: ?>
				<input class="textbox" disabled="disabled" type="text" value="<?= $this->escape($data) ?>" />
				<input type="hidden" name="<?= $prefix.'_'.$name ?>" value="" />
<? endif; ?>
			</p>
<? endforeach; ?>
<? endforeach; ?>
<? endif; ?>
		</fieldset>
<? if (!empty($toolbar)): ?>
		<div class="toolbar">
<? if ($can_add_meta): ?>
			<a class="meta_close_link" href="" title="Add Meta"><img alt="plus" src="<?= $image_root.'plus.png' ?>" /></a>
<? endif; ?>
		</div>
<? endif; ?>
	</div>
</div>
