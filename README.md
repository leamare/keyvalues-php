# Simple KeyValue transformer

Simple Valve KeyValue/VData file formats parser library for PHP. Works with Dota 2 and Deadlock files.

## What is this?

Valve is using JSON-like format KeyValues to store some kind of data (e.g. resources list, scripts, materials, dota 2 build files, etc...)

Details: https://developer.valvesoftware.com/wiki/KeyValues

This library was initially made to transform Dota 2 .build files (using the same kind of format) into valid JSON to process it later.

## Functions

Functions use names similar to PHP default functions for JSON

* `kv_decode(string $string, int $flags = 0)`: (array) gets valid KeyValue string and transforms it into PHP accosiative array
* `kv_encode(array $arr, int $tabs = 0)`: (string) transforms associative array into valid Valve KeyValue string

## Notes

* Multiple elements in a row with the same key will be considered as a regular array with this key being its name and all values being array values
* This also works backwards: if you have a regular array and convert it to KV, it will be a set of values with the same name
* If there are multiple brackets in a row without a name, the last used name will be used for all of them (it's not a valid KV tho)

## What's not working and won't (probably) be implemented (for now)

* **Value types** - they don't matter as much in PHP, initially values are preserved as strings after decoding, it's intentional
* **Preprocessor statements** (#include, #define, etc) - they will be preserved as regular values

## KV Decode flags

* `KV_PRESERVE_COMMENTS` - saves comments as "#/comment_nnn" statement (kv_encode saves such statements as comments)
* `KV_VDATA_FORMAT` - parse VDATA input (compatible with regular KV), works with Deadlock data
* `KV_PARSE_TYPES` - parse data types where possible

## KV Encode flags

* `KV_VDATA_OUTPUT` - output VDATA