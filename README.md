# Dbug

## Features

* Optional recursion limiting
* Basic protection against accidental use in production environments
* Supports CLI and web applications
* Formatted, colorized output

# Usage

To get started, include the library in your project. The first file that is loaded is usually a good choice.

```php
include('Dbug.php');
```

Now you're ready to debug a variable

```php
D::bug($yourVariable);
```

Debug a variable without recursion protection

```php
D::bug($yourVariable, true, -1);
```

Check a string for control characters

```php
D::bugString($yourString);
```

Generate a backtrace

```php
D::backtrace();
```

Grant or revoke debugging privileges (This will override all other checks. Use with caution!)

```php
if($clientIsEligibleForDebugging)
	D::authorize();
else
	D::deauthorize();
```
