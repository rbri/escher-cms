# <et:ns:core> #

## Namespace ##
ns

## Type ##
Container

## Description ##
Sets the current (top-most) namespace to the "core" namespace.

## Attributes ##
None

## Usage ##

```
	<et:ns:core>
		.
		.
	/* any non-scoped tags within this block will implicitly prefer the "core" namespace */
		.
		.
	</et:ns:core>
```

## Examples ##

The following example:

```
	<et:ns:core>
		<et:site_name />
		<et:site_slogan />
	</et:ns:core>
```

is equivalent to:

```
	<et:core:site_name />
	<et:core:site_slogan />
```

## Notes ##

It is seldom necessary to use this tag, because the core namespace is placed onto the namespace stack automatically for each page request. However, consider the following special circumstance:

```
	<et:ns:categories>
		.
		.
		.
		<et:category />
		.
		.
		.
	</et:ns:categories>
```

In the above example, the <et:categories:category> tag will be invoked. However, if your intent was to actually invoke the core namespace tag with the same name (<et:core:category>), you could do so as follows:

```
	<et:ns:categories>
		.
		.
		.
		<et:ns:core>
			<et:category />
		</et:ns:core>
		.
		.
		.
	</et:ns:categories>
```

That said, the recommended approach is to use a fully scoped tag in cases such as this, for clarity and disambiguity:

```
	<et:ns:categories>
		.
		.
		.
		<et:core:category />
		.
		.
		.
	</et:ns:categories>
```