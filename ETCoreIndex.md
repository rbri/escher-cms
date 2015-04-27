# <et:core:index> #

## Namespace ##
core

## Type ##
Single

## Description ##
Used within an iterator tag to output the current value of the internal loop counter.

## Attributes ##
None

## Examples ##

```
	<et:core:repeat stop="10">
		<ul>
			<li>
				Page <et:core:index />
			</li>
		</ul>
	</et:core:repeat>
```

will output:

```
	<ul>
		<li>Page 1</li>
		<li>Page 2</li>
		<li>Page 3</li>
		<li>Page 4</li>
		<li>Page 5</li>
		<li>Page 6</li>
		<li>Page 7</li>
		<li>Page 8</li>
		<li>Page 9</li>
		<li>Page 10</li>
	</ul>
```

Since the core namespace is always available, this tag may be abbreviated by omitting the namespace.

```
	<et:index />
```

## See Also ##
[<et:core:repeat>](ETCoreRepeat.md)<br>
<a href='ETPagesCategoriesEach.md'>&lt;et:pages:categories_each&gt;</a><br>
<a href='ETPagesEach.md'>&lt;et:pages:each&gt;</a><br>
<a href='ETChildrenEach.md'>&lt;et:children:each&gt;</a><br>
<a href='ETSiblingsEach.md'>&lt;et:siblings:each&gt;</a><br>
<a href='ETBlocksEach.md'>&lt;et:blocks:each&gt;</a><br>
<a href='ETImagesEach.md'>&lt;et:images:each&gt;</a><br>
<a href='ETFilesEach.md'>&lt;et:files:each&gt;</a><br>
<a href='ETLinksEach.md'>&lt;et:links:each&gt;</a><br>
<a href='ETCategoriesEach.md'>&lt;et:categories:each&gt;</a><br>
<a href='ETCommentsEach.md'>&lt;et:comments::each&gt;</a><br>
<a href='ETArchivesEach.md'>&lt;et:archives::each&gt;</a><br>
<a href='ETArchivesDatesEach.md'>&lt;et:archives:dates_each&gt;</a><br>