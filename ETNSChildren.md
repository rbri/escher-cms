# <et:ns:children> #

## Namespace ##
ns

## Type ##
Container

## Description ##
Sets the current (top-most) namespace to the "children" namespace.

## Attributes ##
None

## Usage ##

```
	<et:ns:children>
		.
		.
		/* any non-scoped tags within this block will implicitly prefer the "children" namespace */
		.
		.
	</et:ns:children>
```

## Examples ##

The following example:

```
	<et:ns:children>
		<et:each>
			<et:if_first>
				<et:title />
				<et:content />
			</et:if_first>
		</et:each>
	</et:ns:children>
```

is equivalent to:

```
	<et:children:each>
		<et:children:if_first>
			<et:children:title />
			<et:children:content />
		</et:children:if_first>
	</et:children:each>
```