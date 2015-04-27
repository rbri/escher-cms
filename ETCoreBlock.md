# <et:core:block> #

## Namespace ##
core

## Type ##
Single

## Description ##
Outputs the specified content block. Block may be specified by ID or by name.

## Attributes ##
|Attribute Name|Legal Values (default is **bold**)|Function|
|:-------------|:---------------------------------|:-------|
|id|integer|if non-empty, specified the ID of the block to output|
|name|non-numeric string|if non empty and no ID specified, the named block will be output|
|default|string|if specified, this text will be output in the event the specified block does not exist<br>if not specified and the block does not exist, an error will be generated</tbody></table>


<h2>Examples ##
The following example outputs the site's base URL.

```
	<et:core:block name="sidebar" />
```


Since the core namespace is always available, this tag may be abbreviated by omitting the namespace.

```
	<et:block name="sidebar" />
```

## See Also ##
[<et:blocks:name>](ETBlocksName.md)<br>
<a href='ETBlocksTitle.md'>&lt;et:blocks:title&gt;</a><br>
<a href='ETBlocksContent.md'>&lt;et:blocks:content&gt;</a><br>
<a href='ETBlocksIfBlock.md'>&lt;et:blocks:if_block&gt;</a><br>
<a href='ETBlocksBlock.md'>&lt;et:blocks:block&gt;</a><br>
<a href='ETBlocksCount.md'>&lt;et:blocks:count&gt;</a><br>
<a href='ETBlocksEach.md'>&lt;et:blocks:each&gt;</a><br>
<a href='ETBlocksIfCategory.md'>&lt;et:blocks:if_category&gt;</a><br>
<a href='ETBlocksIfFirst.md'>&lt;et:blocks:if_first&gt;</a><br>
<a href='ETBlocksIfLast.md'>&lt;et:blocks:if_last&gt;</a><br>