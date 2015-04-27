# <et:core:if\_development> #

## Namespace ##
Core

## Type ##
Container

## Description ##
Conditional tag that outputs its content only if the site's production-status preference is set to "Development".<br>
As with all conditional tags, it can be used in conjunction with <a href='ETElse.md'>&lt;et:else&gt;</a> to output alternate content if the condition is false.<br>
<br>
<h2>Attributes</h2>

None<br>
<br>
<h2>Examples</h2>

The following example outputs an error message if the site is in development mode:<br>
<br>
<pre><code>&lt;et:if_development&gt;<br>
	&lt;et:error_code /&gt;: &lt;et:error_message /&gt;<br>
&lt;/et:if_development&gt;<br>
</code></pre>

<h2>Notes</h2>

Since the core namespace is always available, this tag may be abbreviated by omitting the namespace, as in the code example above.<br>
<br>
<h2>See Also</h2>
<a href='ETElse.md'>&lt;et:else&gt;</a><br>
<a href='ETCoreErrorCode.md'>&lt;et:core:error_code&gt;</a><br>
<a href='ETCoreErrorMessage.md'>&lt;et:core:error_message&gt;</a><br>
<a href='ETCoreErrorStatus.md'>&lt;et:core:error_status&gt;</a><br>
<a href='ETCoreIfDebug.md'>&lt;et:core:if_debug&gt;</a><br>
<a href='ETCoreIfMaintenance.md'>&lt;et:core:if_maintenance&gt;</a><br>
<a href='ETCoreIfProduction.md'>&lt;et:core:if_production&gt;</a><br>
<a href='ETCoreIfStaging.md'>&lt;et:core:if_staging&gt;</a><br>
<a href='ETNSCore.md'>&lt;et:ns:core&gt;</a><br>