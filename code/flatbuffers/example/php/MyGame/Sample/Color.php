<?php
// automatically generated by the FlatBuffers compiler, do not modify

namespace MyGame\Sample;

class Color
{
    const Red = 0;
    const Green = 1;
    const Blue = 2;

    private static $names = array(
        Color::Red=>"Red",
        Color::Green=>"Green",
        Color::Blue=>"Blue",
    );

    public static function Name($e)
    {
        if (!isset(self::$names[$e])) {
            throw new \Exception();
        }
        return self::$names[$e];
    }
}
