$(document).ready(function() {
	$("input:text:visible,input:password:visible,textarea:visible").first().focus();
	$(".flash").pause(3000).fadeOut("slow");
	$("div.field .title a.expand").click(toggleFieldEvent);
	$("div.field .title a.collapse").click(toggleFieldEvent);
	$("#page-list a.expand").click(toggleChildPageEvent);
	$("#page-list a.collapse").click(toggleChildPageEvent);
	$("#cat-list a.expand").click(toggleChildCatEvent);
	$("#cat-list a.collapse").click(toggleChildCatEvent);
	$("#theme-list a.expand").click(toggleChildThemeEvent);
	$("#theme-list a.collapse").click(toggleChildThemeEvent);
	$("#permissions a.expand").click(toggleChildPermsEvent);
	$("#permissions a.collapse").click(toggleChildPermsEvent);
	$("select#add_category").change(addCategory);
	$("a.delete_category_link").click(removeCategory);
	

	if (fieldStates = $.cookie("escherfieldlist"))
	{
		if (fieldStates.path == window.location.pathname)
		{
			for (var id in fieldStates)
			{
				if (id == "path")
				{
					continue;
				}
				if (fieldStates[id] == 1)
				{
					$("#"+id).parent().find("a.collapse").toggleClass("expand").toggleClass("collapse").parent().parent().find(".collapsible").toggleClass("hidden");
				}
				else
				{
					$("#"+id).parent().find("a.expand").toggleClass("expand").toggleClass("collapse").parent().parent().find(".collapsible").toggleClass("hidden");
				}
			}
		}
	}

});

function center(element)
{
	var top = document.documentElement.scrollTop ? document.documentElement.scrollTop : document.body.scrollTop;
	var header = $("#header");
	element.makeAbsolute();
	element.css("top", (top + 200) + "px");
	element.css("left", ((header.width() - element.width()) / 2) + "px");
}

function toggleFieldEvent(event)
{
	event.preventDefault();
	var collapsible = $(this).toggleClass("expand").toggleClass("collapse").parent().parent().find(".collapsible");
	collapsible.slideToggle("medium");
	if (collapsible.hasClass("persistent"))
	{
		var path = window.location.pathname;
		var id = collapsible.attr("id");
		var checkPath = $.subCookie("escherfieldlist", "path");
		if (checkPath != path)
		{
			$.clearCookie("escherfieldlist");
			$.setSubCookie("escherfieldlist", "path", path);
		}
		if (id)
		{
			$.setSubCookie("escherfieldlist", id, $(this).hasClass("expand") ? "1" : "0");
		}
	}
}

function toggleChildPageEvent(event)
{
	event.preventDefault();
	$(this).toggleClass("expand").toggleClass("collapse").parent().parent().parent().parent().children(".collapsible").slideToggle("medium");
	if ($(this).closest(".hier-list").hasClass("persistent"))
	{
		var id = $(this).closest("li").attr("id").substr(5);
		$.setSubCookie("escherpagelist", id, $(this).hasClass("expand") ? "1" : "0");
	}
}

function toggleChildCatEvent(event)
{
	event.preventDefault();
	$(this).toggleClass("expand").toggleClass("collapse").parent().parent().parent().parent().children(".collapsible").slideToggle("medium");
	if ($(this).closest(".hier-list").hasClass("persistent"))
	{
		var id = $(this).closest("li").attr("id").substr(9);
		$.setSubCookie("eschercatlist", id, $(this).hasClass("expand") ? "1" : "0");
	}
}

function toggleChildThemeEvent(event)
{
	event.preventDefault();
	$(this).toggleClass("expand").toggleClass("collapse").parent().parent().parent().parent().children(".collapsible").slideToggle("medium");
	if ($(this).closest(".hier-list").hasClass("persistent"))
	{
		var id = $(this).closest("li").attr("id").substr(6);
		$.setSubCookie("escherthemelist", id, $(this).hasClass("expand") ? "1" : "0");
	}
}

function toggleChildPermsEvent(event)
{
	event.preventDefault();
	$(this).toggleClass("expand").toggleClass("collapse").closest("li").next(".collapsible").slideToggle("medium");
}

function addCategory(event)
{
	var selected = $("select#add_category option:selected");		
	var cat_id = selected.val();
	if (cat_id != 0)
	{
		var categoryName = selected.text().trim() + " ";
		$("span#categories").append('<span id="cat_' + cat_id + '"><a class="delete_category_link" href="">' + categoryName + '</a><input type="hidden" name="add_categories[]" value="' + cat_id + '" /></span>');
		$("span#categories span#cat_"+cat_id+" a").click(removeCategory);
	}
	$("select#add_category").val(0);
}

function removeCategory(event)
{
	event.preventDefault();
	$(this).parent().remove();
}
