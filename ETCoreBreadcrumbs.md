# <et:core:breadcrumbs> #

## Namespace ##
core

## Type ##
Single

## Description ##
Outputs a breadcrumb trail, formatted as an ordered list, for the current page context.

## Attributes ##
|Attribute Name|Legal Values (default is **bold**)|Function|
|:-------------|:---------------------------------|:-------|
|separator|string|text to appear between each breadcrumb in the trail|
|aslinks|**true**, false,|whether to make each breadcrumb a clickable link|
|id|css string|optional css id to apply to the ordered list element|
|class|css string|optional css class to apply to the ordered list element|
|withmagic|true, **false**|whether to generate breadcrumbs for virtual pages|


## Examples ##

```
	<et:core:breadcrumbs separator=">>" class="breadcrumbs" />
```

will output something like the following:

```
	<ol class="breadcrumbs">
		<li><a href="http://mysite.com/">Home</a>&gt;&gt;</li>
		<li><a href="http://mysite.com/about">About</a>&gt;&gt;</li>
		<li><a href="http://mysite.com/about/contact-us">Contact Us</a>&gt;&gt;</li>
		<li>Overview</li>
	</ol>
```

Since the core namespace is always available, this tag may be abbreviated by omitting the namespace.

```
	<et:breadcrumbs separator=">>" class="breadcrumbs" />
```