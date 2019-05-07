<?php

namespace Idimption;

class Html
{
    public static function section($html)
    {
        return "<div style='margin: 5px 0;'>$html</div>";
    }

    public static function multiline($html)
    {
        return "<div style='white-space: pre-wrap;'>$html</div>";
    }

    public static function bold($html)
    {
        return "<span style='font-weight: bold;'>$html</span>";
    }

    public static function diffDelete($html)
    {
        return "<span style='text-decoration: line-through; background-color: #f2dede; color: #a94442;'>$html</span>";
    }

    public static function diffAdd($html)
    {
        return "<span style='background-color: #dff0d8; color: #3c763d;'>$html</span>";
    }
}
