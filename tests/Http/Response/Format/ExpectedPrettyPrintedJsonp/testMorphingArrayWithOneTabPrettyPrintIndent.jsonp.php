<?php

/*
 * Pretty printed JSONP with one tab indent
 *
 * @return string
 */

return <<<'JSON'
foo({
	"foo": "bar",
	"baz": {
		"foobar": [
			42,
			0.00042,
			"",
			null
		]
	}
});
JSON;
