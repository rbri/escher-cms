# <et:core:link> #

## Namespace ##
core

## Type ##
Single or Container

## Description ##
Outputs a hyperlink to the specified link. Link may be specified by ID or by name.

## Attributes ##
|Attribute Name|Legal Values (default is **bold**)|Function|
|:-------------|:---------------------------------|:-------|
|id|integer|if non-empty, the ID of the link to output|
|name|non-numeric string|if non-empty and no ID specified, the name of the link to output|
|title|string|if non-empty, this text will be used for the anchor tag's title attribute<br>if not specified the link's title will be used<br>
<tr><td><i>(tag atts)</i></td><td>string</td><td>any additional attributes will be passed directly to the anchor tag</td></tr></tbody></table>


<h2>Examples ##
Output a hyperlink, using the link's title as the link text:

```
	<et:core:link id="37" />
```

Output a hyperlink, specifying the link text and an additional tag attribute

```
	<et:core:link id="37" rel="Bookmark">Read more (external link)</et:core:file>
```

Since the core namespace is always available, this tag may be abbreviated by omitting the namespace.

```
	<et:link id="37" />
```

## Notes ##

The "links" namespace has its own "link" tag, with a different function, so do not use the short form inside the "links" namespace unless you really want <et:links:link>.

In general it is preferable to specify links by ID instead of name. Both are unique identifiers, but ID will always refer to the same database record, whereas the name can be changed.

## See Also ##
[<et:ns:links>](ETNSLinks.md)<br>
<a href='ETLinksAnchor.md'>&lt;et:links:anchor&gt;</a><br>
<a href='ETLinksCount.md'>&lt;et:links:count&gt;</a><br>
<a href='ETLinkssescription.md'>&lt;et:links:description&gt;</a><br>
<a href='ETLinksEach.md'>&lt;et:links:each&gt;</a><br>
<a href='ETLinksIfCategory.md'>&lt;et:links:if_category&gt;</a><br>
<a href='ETLinksIfFirst.md'>&lt;et:links:if_first&gt;</a><br>
<a href='ETLinksIfHere.md'>&lt;et:links:if_here&gt;</a><br>
<a href='ETLinksIfLast.md'>&lt;et:links:if_last&gt;</a><br>
<a href='ETLinksIfSelected.md'>&lt;et:links:if_selected&gt;</a><br>
<a href='ETLinksLink.md'>&lt;et:links:link&gt;</a><br>
<a href='ETLinksName.md'>&lt;et:links:name&gt;</a><br>
<a href='ETLinksSize.md'>&lt;et:links:size&gt;</a><br>
<a href='ETLinksTitle.md'>&lt;et:links:title&gt;</a><br>
<a href='ETLinksUrl.md'>&lt;et:links:url&gt;</a><br>