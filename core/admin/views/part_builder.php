<script type="text/javascript">
	function generatePart(part_kind, part_name)
	{
		var partHTML = new Array();
<? foreach ($callbacks as $type => $callback): ?>
		partHTML['<?= $type ?>'] = '<?= call_user_func($callback, array('name'=>'{part_name}', 'val'=>'')) ?>';
<? endforeach; ?>		
		return partHTML[part_kind].replace(/{part_name}/g, part_name);
	}
	
	function removePart(event)
	{
		event.preventDefault();
		var part_num = $(this).attr("name");
		var part_name = $(this).attr("alt");
		if (confirm('Are you sure you want to delete part "' + part_name + '"?'))
		{
			var part_field_id = "page_part_" + part_num + "_field";
			$("#parts div#"+part_field_id+" .collapsible").slideUp("medium", function(){ $(this).parent().remove(); });
		}
	}
	
	$(document).ready(function() {

		$("a.part_delete_link").click(removePart);

		$("select#add_part").change(function(event) {
			var part_kind = $(this).val();
			if (part_kind != 0)
			{
				var part_num = $(".new_page_part").length+1;
				var part_name = "page_part_" + part_num;
				var part_field_id = part_name + "_field";
				var part_name_id = part_name + "_name";
				var part_content_id = part_name + "_content";
				var part_type = part_name + "_type";
				var part_name_text = "New Part #" + part_num;
		
				$("div#parts fieldset").append('\
					<div id="'+ part_field_id + '" class="field new_page_part">\
						<label class="title" for="'+ part_name_id + '"><a class="collapse" href="">New Part </a></label>\
						<input type="text" id="'+ part_name_id + '" name="'+ part_name_id + '" value="Part Name" />\
						<a class="part_delete_link" name="' + part_num + '" alt="' + part_name_text + '" href=""><img title="Delete Part &quot;' + part_name_text + '&quot;" alt="delete part" src="<?= $image_root.'minus.png' ?>" /></a>\
						<div id="f' + part_name + '" class="collapsible persistent">\
							<input type="hidden" name="' + part_type + '" value="' + part_kind + '" />\
							' + generatePart(part_kind, part_content_id) + '\
						</div>\
					</div>\
				');
				$("#"+part_field_id+" a.part_delete_link").click(removePart)
				$("#"+part_field_id+" .title a").click(toggleFieldEvent);
				$("#"+part_name_id).focus();
			}
			$(this).val(0);
		});
		
	});
</script>

<div id="parts">
	<fieldset>
		<? $i = 0; foreach($parts as $name => $part): ?>
		<? $partName = "page_part_{$name}_name"; $partContent = "page_part_{$name}_content"; $filterClass = ''; ?>
		<? $showField = ((($i === 0) && empty($errors)) || isset($errors[$partContent])); ?>
<? if (!empty($part->new)): ?>
		<div id="<?= "page_part_{$name}_field" ?>" class="field new_page_part">
<? if (isset($errors[$partName])): ?>
			<label class="title error" for="<?= $partName ?>"><a class="<?= $showField ? 'collapse' : 'expand' ?>" href="">New Part </a></label>
			<input type="text" id="<?= $partName ?>" name="<?= $partName ?>" value="<?= $this->escape($part->name) ?>" />
<? else: ?>
			<label class="title<?= isset($errors[$partContent]) ? ' error' : '' ?>" for="<?= $partName ?>"><a href=""><?= $this->escape($part->name) ?> </a></label>
			<input type="hidden" id="<?= $partName ?>" name="<?= $partName ?>" value="<?= $this->escape($part->name) ?>" />
<? endif; ?>
<? else: ?>
		<div id="<?= "page_part_{$name}_field" ?>" class="field">
			<label class="title<?= isset($errors[$partContent]) ? ' error' : '' ?>" for="<?= $partContent ?>"><a class=" <?= $showField ? 'collapse' : 'expand' ?>" href=""><?= $this->escape($part->name) ?></a></label>
<? endif; ?>
<? if ($can_delete_parts): ?>
			<a class="part_delete_link" name="<?= $this->escape($name) ?>" alt="<?= $this->escape($name) ?>" href=""><img title="Delete Part <?= '&quot;'.$this->escape($name).'&quot;' ?>" alt="delete part" src="<?= $image_root.'minus.png' ?>" /></a>
<? endif; ?>
			<?= isset($errors[$partName]) ? "<div class=\"clear error\">{$this->escape($errors[$partName])}</div>" : '' ?>
			<div id="<?= "f{$name}" ?>" class="collapsible persistent<?= !$showField ? ' hidden' : '' ?>">
				<input type="hidden" name="<?= "page_part_{$name}_type" ?>" value="<?= $part->type ?>" />
<? if ($can_edit_parts): ?>
<? if (!empty($filterNames) && ($part->type === 'textarea')): ?>
			<div class="filter">
				<label for="<?= "{$partContent}_filter" ?>">Filter:</label>
				<select name="<?= "{$partContent}_filter" ?>" id="<?= "{$partContent}_filter" ?>">
<? foreach($filterNames as $filterID => $filterName): ?>
	<? if ($filterID == $part->filter_id): ?>
		<? $filterClass = $filterClasses[$filterID]; ?>
					<option value="<?= $filterID ?>" selected="selected"><?= $filterName ?></option>
	<? else: ?>
					<option value="<?= $filterID ?>"><?= $filterName ?></option>
	<? endif; ?>
<? endforeach; ?>
				</select>
			</div>
<? endif; ?>
				<?= call_user_func($callbacks[$part->type], array('class'=>$filterClass, 'name'=>$partContent, 'val'=>$part->content)) ?>
<? else: ?>
				<?= call_user_func($callbacks[$part->type], array('disabled'=>true, 'name'=>"page_part_{$name}_disabled", 'val'=>$part->content)) ?>
				<input type="hidden" name="page_part_<?= $name ?>_content" val="" />
<? endif; ?>
			</div>
			<?= isset($errors[$partContent]) ? "<div class=\"clear error\">{$this->escape($errors[$partContent])}</div>" : '' ?>
		</div>
		<? ++$i; endforeach; ?>
	</fieldset>
	<div class="toolbar">

		<div class="toolbar" style="padding:5px 0 5px 0;">
<? if ($can_add_parts): ?>
		<select name="add_part" id="add_part">
			<option value="0">Add Part</option>
<? foreach($callbacks as $type => $callback): ?>
			<option value="<?= $type ?>"><?= ucwords($type) ?></option>
<? endforeach; ?>
		</select>
<? endif; ?>
		</div>

	</div>
</div>
