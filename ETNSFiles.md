# <et:ns:files> #

## Namespace ##
ns

## Type ##
Container

## Description ##
Sets the current (top-most) namespace to the "files" namespace.

## Attributes ##
None

## Usage ##

```
	<et:ns:files>
		.
		.
	/* any non-scoped tags within this block will implicitly prefer the "files" namespace */
		.
		.
	</et:ns:files>
```

## Examples ##

The following example:

```
	<et:ns:files>
		<et:each>
			<et:name />
			<et:title />
			<et:status />
		</et:each>
	</et:ns:files>
```

is equivalent to:

```
	<et:files:each>
		<et:files:name />
		<et:files:title />
		<et:files:status />
	</et:files:each>
```