# 

&lt;et:else&gt;

 #

## Namespace ##
None

## Type ##
Single

## Description ##
Specifies alternate behavior in the case that the preceeding conditional tag evaluates to false.

## Attributes ##
None

## Examples ##

The following example outputs a summary if one exists, otherwise the main body content.

```
<et:if_content name="summary">
	<et:content part="summary" />
<et:else />
	<et:content part="body" />
</et:if_content>
```