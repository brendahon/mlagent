<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit78d863888d773324883de6bba225fda7
{
    public static $prefixLengthsPsr4 = array (
        'L' => 
        array (
            'Lcobucci\\JWT\\' => 13,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Lcobucci\\JWT\\' => 
        array (
            0 => __DIR__ . '/..' . '/lcobucci/jwt/src',
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit78d863888d773324883de6bba225fda7::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit78d863888d773324883de6bba225fda7::$prefixDirsPsr4;

        }, null, ClassLoader::class);
    }
}