# Simple Valve KeyValue transformer

## What is this?

Valve is using JSON-like format KeyValues to store some kind of data (e.g. resources list, scripts, materials, dota 2 build files, etc...)

Details: https://developer.valvesoftware.com/wiki/KeyValues

This library was initially made to transform Dota 2 .build files (using the same kind of format) into valid JSON to process it later.

## Functions

Functions use names similar to PHP default functions for JSON

* `kv_decode(string $string)`: (array) gets valid KeyValue string and transforms it into PHP accosiative array
* `kv_encode(array $arr, int $tabs = 0)`: (string) transforms associative array into valid Valve KeyValue string

## Notes

* Multiple elements in a row with the same key will be considered as a regular array with this key being its name and all values being array values
* This also works backwards: if you have a regular array and convert it to KV, it will be a set of values with the same name
* If there are multiple brackets in a row without a name, the last used name will be used for all of them (it's not a valid KV tho)

## TODO:

- [ ] Comments support
  - comments types: "//...", "/..." and "/* */" (the last one isn't intended to be supported)
  - kv_decode flag to ignore comments or save them separately
- [ ] Value type detection
- [ ] Support for non-quoted tokens
- [ ] Conditional statements support
- [ ] Size limitation for KeyValues support (max token length is 1021)
- [ ] fix arrays of brackets