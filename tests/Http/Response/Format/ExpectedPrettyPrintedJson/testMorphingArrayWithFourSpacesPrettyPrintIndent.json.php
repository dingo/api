<?php

/*
 * Pretty printed JSON with 4 spaces indent
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
