<?php
namespace ns;
?>
--TEST--
Bug #49092 (ReflectionFunction fails to work with functions in fully qualified namespaces)
--FILE--
<?php
function func(){}
new \ReflectionFunction('ns\func');
new \ReflectionFunction('\ns\func');
echo "Ok\n"
?>
--EXPECT--
Ok
