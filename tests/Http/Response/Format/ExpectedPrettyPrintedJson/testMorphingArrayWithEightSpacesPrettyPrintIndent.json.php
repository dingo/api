<?php

/*
 * Pretty printed JSON with 8 spaces indent
 *
 * @return string
 */

return <<<'JSON'
{
        "foo": "bar",
        "baz": {
                "foobar": [
                        42,
                        0.00042,
                        "",
                        null
                ]
        }
}
JSON;
