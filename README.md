# Niirrty.ClassReflector

A PHP Tool/Class for creating a signature dump for any known PHP class, and it parts.

Example usage:

```php
use \Niirrty\Reflection\ClassReflector;

$reflector = new ClassReflector( '\My\Class', true );

echo '&lt;pre&gt;';
echo $reflector->generatePHPSignatures();
```
