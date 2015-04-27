# <et:core:file> #

## Namespace ##
core

## Type ##
Single or Container

## Description ##
Outputs a hyperlink to the specified file. File may be specified by ID or by name.

## Attributes ##
|Attribute Name|Legal Values (default is **bold**)|Function|
|:-------------|:---------------------------------|:-------|
|id|integer|if non-empty, specified the ID of the file to link to|
|name|non-numeric string|if non-empty and no ID specified, the named file will be linked to|
|title|string|if non-empty, this text will be used for the anchor tag's title attribute<br>if not specified the file's title will be used<br>
<tr><td><i>(tag atts)</i></td><td>string</td><td>any additional attributes will be passed directly to the anchor tag</td></tr></tbody></table>


<h2>Examples ##
Output a hyperlink to a file, using the file title as the link text:

```
	<et:core:file id="37" />
```

Output a hyperlink to a file, specifying the link text and an additional tag attribute

```
	<et:core:file id="37" type="text/rtf">Download</et:core:file>
```

Since the core namespace is always available, this tag may be abbreviated by omitting the namespace.

```
	<et:file id="37" />
```

## Notes ##

The "files" namespace has its own "file" tag, with a different function, so do not use the above form inside the "files" namespace unless you really want <et:files:file>.

In general it is preferable to specify files by ID instead of name. Both are unique identifiers, but ID will always refer to the same database record, whereas the name can be changed.

## See Also ##
[<et:ns:files>](ETNSFiles.md)<br>
<a href='ETFilesAnchor.md'>&lt;et:files:anchor&gt;</a><br>
<a href='ETFilesCount.md'>&lt;et:files:count&gt;</a><br>
<a href='ETFilessescription.md'>&lt;et:files:description&gt;</a><br>
<a href='ETFilesEach.md'>&lt;et:files:each&gt;</a><br>
<a href='ETFilesFile.md'>&lt;et:files:file&gt;</a><br>
<a href='ETFilesIfCategory.md'>&lt;et:files:if_category&gt;</a><br>
<a href='ETFilesIfFirst.md'>&lt;et:files:if_first&gt;</a><br>
<a href='ETFilesIfLast.md'>&lt;et:files:if_last&gt;</a><br>
<a href='ETFilesName.md'>&lt;et:files:name&gt;</a><br>
<a href='ETFilesSize.md'>&lt;et:files:size&gt;</a><br>
<a href='ETFilesStatus.md'>&lt;et:files:status&gt;</a><br>
<a href='ETFilesTitle.md'>&lt;et:files:title&gt;</a><br>
<a href='ETFilesUrl.md'>&lt;et:files:url&gt;</a><br>