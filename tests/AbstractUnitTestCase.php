<?php

namespace Tests;

use PHPUnit\Framework\TestCase;

class AbstractUnitTestCase extends TestCase
{
    /**
     * getPrivateMethod
     *
     * @param    string $className
     * @param    string $methodName
     * @return   \ReflectionMethod
     */
    public function getPrivateMethod($className, $methodName)
    {
        $reflector = new \ReflectionClass($className);
        $method = $reflector->getMethod($methodName);
        $method->setAccessible(true);
        
        return $method;
    }
}
