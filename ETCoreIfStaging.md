# <et:core:if\_staging> #

## Namespace ##
Core

## Type ##
Container

## Description ##
Conditional tag that outputs its content only if the site's production status is "Staging".<br>
As with all conditional tags, it can be used in conjunction with <a href='ETElse.md'>&lt;et:else&gt;</a> to output alternate content if the condition is false.<br>
<br>
<h2>Attributes</h2>

None<br>
<br>
<h2>Examples</h2>

The following example redirects unauthenticated users to a login page if the site's production status is "Staging":<br>
<br>
<pre><code>&lt;et:if_staging&gt;<br>
	&lt;et:cookie:if_cookie name="login"&gt;<br>
	&lt;et:else /&gt;<br>
		&lt;et:redirect target="/login.php" /&gt;<br>
	&lt;/et:cookie:if_cookie&gt;<br>
&lt;/et:if_staging&gt;<br>
</code></pre>

<h2>Notes</h2>

Since the core namespace is always available, this tag may be abbreviated by omitting the namespace, as in the code example above.<br>
<br>
<h2>See Also</h2>
<a href='ETElse.md'>&lt;et:else&gt;</a><br>
<a href='ETCoreIfDebug.md'>&lt;et:core:if_debug&gt;</a><br>
<a href='ETCoreIfDevelopment.md'>&lt;et:core:if_development&gt;</a><br>
<a href='ETCoreIfMaintenance.md'>&lt;et:core:if_maintenance&gt;</a><br>
<a href='ETCoreIfProduction.md'>&lt;et:core:if_production&gt;</a><br>
<a href='ETNSCore.md'>&lt;et:ns:core&gt;</a><br>