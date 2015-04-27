# <et:core:site\_url> #

## Namespace ##
core

## Type ##
Single

## Description ##
Outputs the site's URL as specified in the Site Preferences.

## Attributes ##
|Attribute Name|Legal Values (default is **bold**)|Function|
|:-------------|:---------------------------------|:-------|
|full|**true**, false,|whether to output a full URL with scheme and host|
|secure|true, **false**| whether to output the alternate secure site URL|

## Examples ##

The following example outputs the site's URL.

```
	<et:core:site_url />
```

The following example outputs the site's secure URL.

```
	<et:core:site_url secure="true" />
```

Since the core namespace is always available, this tag may be abbreviated by omitting the namespace.

```
	<et:site_url />
```

## See Also ##
[<et:core:base\_url>](ETCoreBaseURL.md)<br>
<a href='ETPhoneHome.md'>&lt;et:phone:home&gt;</a><br>