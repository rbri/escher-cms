# <et:ns:pages> #

## Namespace ##
ns

## Type ##
Container

## Description ##
Sets the current (top-most) namespace to the "pages" namespace.

## Attributes ##
None

## Usage ##

```
	<et:ns:pages>
		.
		.
	/* any non-scoped tags within this block will implicitly prefer the "pages" namespace */
		.
		.
	</et:ns:pages>
```

## Examples ##

The following example:

```
	<et:ns:pages>
		<et:each />
			<et:title />
			<et:content />
		</et:each >
	</et:ns:pages>
```

is equivalent to:

```
	<et:pages:each />
		<et:pages:title />
		<et:pages:content />
	</et:pages:each >
```

## Notes ##

It is seldom necessary to use this tag, because the pages namespace is placed onto the namespace stack automatically for each page request. However, consider the following special circumstance:

```
	<et:ns:files>
		<et:each>
			<et:title />
			<et:url />
		</et:each>
	</et:ns:files>
```

In the above example, the <et:files:title> tag will be invoked. However, if your intent was to actually invoke the pages namespace tag with the same name (<et:pages:title>), you could do so as follows:

```
	<et:ns:files>
		<et:each>
			<et:ns:pages>
				<et:title />
			</et:ns:pages>
			<et:url />
		</et:each>
	</et:ns:files>
```

That said, the recommended approach is to use a fully scoped tag in cases such as this, for clarity and disambiguity:

```
	<et:ns:files>
		<et:each>
			<et:pages:title />
			<et:url />
		</et:each>
	</et:ns:files>
```