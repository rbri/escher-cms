# <et:core:base\_url> #

## Namespace ##
core

## Type ##
Single

## Description ##
Outputs the base URL to your site. If you are not using clean URLs your site URL as returned by the <et:core:site\_url> tag will include your site's index file (eg index.php). The base URL omits the index file and is useful for generating URLs to static assets not managed by Escher.

## Attributes ##
|Attribute Name|Legal Values (default is **bold**)|Function|
|:-------------|:---------------------------------|:-------|
|full|**true**, false,|whether to output a full URL with scheme and host|
|secure|true, **false**| whether to output the alternate secure site URL|


## Examples ##
The following example outputs the site's base URL.

```
	<et:core:base_url />
```

The following example outputs the site's secure base URL.

```
	<et:core:base_url secure="true" />
```

Since the core namespace is always available, this tag may be abbreviated by omitting the namespace.

```
	<et:base_url />
```

## See Also ##
[<et:core:site\_url>](ETCoreSiteURL.md)