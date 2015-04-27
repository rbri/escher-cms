# <et:core:category> #

## Namespace ##
core

## Type ##
Single or Container

## Description ##
Outputs current category name, title or hyperlink on a category listing page.

## Attributes ##
|Attribute Name|Legal Values (default is **bold**)|Function|
|:-------------|:---------------------------------|:-------|
|title|true, **false**|whether to output category title instead of slug (ignored if link===true)|
|link|true, **false**|whether to output category hyperlink|

## Examples ##

As a single tag, output the category name (slug):

```
	<et:core:category />
```

As a single tag, output the category title:

```
	<et:core:category title="true" />
```

As a single tag, output the category as a hyperlink:

```
	<et:core:category link="true" />
```

As a container tag, output the category as a hyperlink with custom link text:

```
	<et:core:category>View items in this category</et:core:category>
```

Since the core namespace is always available, this tag may be abbreviated by omitting the namespace.

```
	<et:category />
```

## See Also ##

[<et:pages:categories\_each>](ETPagesCategoriesEach.md)<br>
<a href='ETPagesIfCategory.md'>&lt;et:pages:if_category&gt;</a><br>
<a href='ETBlocksIfCategory.md'>&lt;et:blocks:if_category&gt;</a><br>
<a href='ETImagesIfCategory.md'>&lt;et:images:if_category&gt;</a><br>
<a href='ETFilesIfCategory.md'>&lt;et:files:if_category&gt;</a><br>
<a href='ETLinksIfCategory.md'>&lt;et:links:if_category&gt;</a><br>
<a href='ETCategoriesID.md'>&lt;et:categories:id&gt;</a><br>
<a href='ETCategoriesName.md'>&lt;et:categories:name&gt;</a><br>
<a href='ETCategoriesTitle.md'>&lt;et:categories:title&gt;</a><br>
<a href='ETCategoriesUsed.md'>&lt;et:categories:used&gt;</a><br>
<a href='ETCategoriesURL.md'>&lt;et:categories:url&gt;</a><br>
<a href='ETCategoriesAnchor.md'>&lt;et:categories:anchor&gt;</a><br>
<a href='ETCategoriesCount.md'>&lt;et:categories:count&gt;</a><br>
<a href='ETCategoriesCategory.md'>&lt;et:categories:category &gt;</a><br>
<a href='ETCategoriesEach.md'>&lt;et:categories:each&gt;</a><br>
<a href='ETCategoriesChildren.md'>&lt;et:categories:children&gt;</a><br>
<a href='ETCategoriesIfFirst.md'>&lt;et:categories:if_first&gt;</a><br>
<a href='ETCategoriesIfLast.md'>&lt;et:categories:if_last&gt;</a><br>
<a href='ETCategoriesIfHere.md'>&lt;et:categories:if_here&gt;</a><br>
<a href='ETCategoriesIfSelected.md'>&lt;et:categories:if_selected&gt;</a><br>
<a href='ETCategoriesIfHasChild.md'>&lt;et:categories:if_has_child&gt;</a><br>