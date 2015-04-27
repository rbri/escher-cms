# <et:ns:siblings> #

## Namespace ##
ns

## Type ##
Container

## Description ##
Sets the current (top-most) namespace to the "siblings" namespace.

## Attributes ##
None

## Usage ##

```
	<et:ns:siblings>
		.
		.
	/* any non-scoped tags within this block will implicitly prefer the "siblings" namespace */
		.
		.
	</et:ns:siblings>
```

## Examples ##

The following example:

```
	<et:ns:siblings>
		<et:each>
			<et:if_first>
				<et:pages:title />
			</et:if_first>
		</et:each>
	</et:ns:siblings>
```

is equivalent to:

```
	<et:siblings:each>
		<et:siblings:if_first>
			<et:pages:title />
		</et:siblings:if_first>
	</et:siblings:each>
```