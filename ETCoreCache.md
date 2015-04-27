# <et:core:cache> #

## Namespace ##
core

## Type ##
Container

## Description ##
Caches a block of content to the _Partial Cache_ for faster subsequent display within the current page request or subsequent page requests. A timeout value may be optionally provided via the _timeout_ attribute. If no timeout is provided, the timeout setting in the Escher Admin _Performance_ preferences pane will be used.

## Attributes ##
|Attribute Name|Legal Values (default is **bold**)|Function|
|:-------------|:---------------------------------|:-------|
|id|string|uniquely identifies this block of content|
|global|true, **false**|whether this block should be cached once for all pages; by default cache blocks are page-specific|
|timeout|integer|the lifetime of this cache block in seconds|


## Examples ##

```
	<et:core:cache id="sidebar">
		<ul>
			<et:pages:each category="sidebar">
				<li>
					<et:pagelink />
				</li>
			</et:pages:each>
		</ul>
	</et:core:cache>
```

Since the core namespace is always available, this tag may be abbreviated by omitting the namespace.

```
	<et:cache id="sidebar">
		<ul>
			<et:pages:each category="sidebar">
				<li>
					<et:pagelink />
				</li>
			</et:pages:each>
		</ul>
	</et:cache>
```

## Notes ##

Although the **id** attribute is optional, it is recommended that you always provide it both for clarity of code and so that the cache does not operate unexpectedly in the presence of conditional tags (which could cause some cache blocks to be executed on some pages and not on others). If you do not provide an id, Escher will generate one for you via a simple auto-increment counter. This works fine if the same cache blocks are encountered in the same order for all page requests. But this will likely not be true for pages of any significant complexity - especially when the **global** attribute is set to true.