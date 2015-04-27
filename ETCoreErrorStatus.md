# <et:core:error\_status> #

## Namespace ##
core

## Type ##
Single

## Description ##
Ouputs the error status for the most recently encountered exception. Used in error page templates to display the error to the user.

## Attributes ##
None

## Examples ##

The following example:

```
	<et:core:error_status />
```

Produces something like:
```
	404 Not Found
```

Since the core namespace is always available, this tag may be abbreviated by omitting the namespace.

```
	<et:error_status />
```

## See Also ##
[<et:core:error\_code>](ETCoreErrorCode.md)<br>
<a href='ETCoreErrorMessage.md'>&lt;et:core:error_message&gt;</a><br>
<a href='ETCoreIfDebug.md'>&lt;et:core:if_debug&gt;</a><br>
<a href='ETCoreIfDevelopment.md'>&lt;et:core:if_development&gt;</a><br>
<a href='ETCoreIfMaintenance.md'>&lt;et:core:if_maintenance&gt;</a><br>
<a href='ETCoreIfProduction.md'>&lt;et:core:if_production&gt;</a><br>
<a href='ETCoreIfStaging.md'>&lt;et:core:if_staging&gt;</a><br>
<a href='ETNSCore.md'>&lt;et:ns:core&gt;</a><br>