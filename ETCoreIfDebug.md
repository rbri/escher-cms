# <et:core:if\_debug> #

## Namespace ##
Core

## Type ##
Container

## Description ##
Conditional tag that outputs its content only if the site's debug-level preference meets or exceeds the specified level.<br>
As with all conditional tags, it can be used in conjunction with <a href='ETElse.md'>&lt;et:else&gt;</a> to output alternate content if the condition is false.<br>
<br>
<h2>Attributes</h2>
<table><thead><th>Attribute Name</th><th>Legal Values (default is <b>bold</b>)</th><th>Function</th></thead><tbody>
<tr><td>level</td><td>integer <b>1</b></td><td>Minimum debug level to match the condition so that the tag evaluates as true</td></tr></tbody></table>

<h2>Examples</h2>

The following example outputs an error message if the site's debug_level preference is set to at least 3.<br>
<br>
<pre><code>&lt;et:if_debug level="3"&gt;<br>
	&lt;et:error_code /&gt;: &lt;et:error_message /&gt;<br>
&lt;/et:if_content&gt;<br>
</code></pre>

<h2>Notes</h2>

Debug levels range from 0 to 9<br>
<br>
Since the core namespace is always available, this tag may be abbreviated by omitting the namespace, as in the code example above.<br>
<br>
<h2>See Also</h2>
<a href='ETElse.md'>&lt;et:else&gt;</a><br>
<a href='ETCoreErrorCode.md'>&lt;et:core:error_code&gt;</a><br>
<a href='ETCoreErrorMessage.md'>&lt;et:core:error_message&gt;</a><br>
<a href='ETCoreErrorStatus.md'>&lt;et:core:error_status&gt;</a><br>
<a href='ETCoreIfDevelopment.md'>&lt;et:core:if_development&gt;</a><br>
<a href='ETCoreIfMaintenance.md'>&lt;et:core:if_maintenance&gt;</a><br>
<a href='ETCoreIfProduction.md'>&lt;et:core:if_production&gt;</a><br>
<a href='ETCoreIfStaging.md'>&lt;et:core:if_staging&gt;</a><br>
<a href='ETNSCore.md'>&lt;et:ns:core&gt;</a><br>