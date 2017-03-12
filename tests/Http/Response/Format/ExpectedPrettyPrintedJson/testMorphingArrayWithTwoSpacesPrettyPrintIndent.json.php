<?php

/*
 * Pretty printed JSON with 2 spaces indent
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
