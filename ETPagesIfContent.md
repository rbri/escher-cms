# <et:pages:if\_content> #

## Namespace ##
Pages

## Type ##
Container

## Description ##
Conditional tag that outputs its content only if the current page has content.<br>
As with all conditional tags, it can be used in conjunction with <a href='ETElse.md'>&lt;et:else&gt;</a> to output alternate content if the condition is false.<br>
<br>
<h2>Attributes</h2>
<table><thead><th>Attribute Name</th><th>Legal Values (default is <b>bold</b>)</th><th>Function</th></thead><tbody>
<tr><td>part</td><td>string <b>body</b></td><td>Comma-separated list of page parts to check for content.<br>If empty, check if current page has at least one part.</td></tr>
<tr><td>inherit</td><td>boolean <b>false</b></td><td>If set, also check ancestor pages for specified content types.</td></tr>
<tr><td>find</td><td>string <b>empty</b></td><td>If "any", simply check if the page (or any ancestor, if "inherit" is set) has any of the parts named in the "part" attribute.</td></tr></tbody></table>

<h2>Examples</h2>

The following example outputs a summary if one exists, otherwise the main body content. Since the pages namespace is always available, this tag (and the <et:pages:content> tag) may be abbreviated by omitting the namespace.<br>
<br>
<pre><code>&lt;et:if_content name="summary"&gt;<br>
	&lt;et:content part="summary" /&gt;<br>
&lt;et:else /&gt;<br>
	&lt;et:content part="body" /&gt;<br>
&lt;/et:if_content&gt;<br>
</code></pre>

<h2>See Also</h2>
<a href='ETElse.md'>&lt;et:else&gt;</a><br>
<a href='ETPagesContent.md'>&lt;et:pages:content&gt;</a><br>