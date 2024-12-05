<?php 

namespace leamare\SimpleKV;

const KV_PRESERVE_COMMENTS = 1;
const KV_VDATA_FORMAT = 2;
const KV_PARSE_TYPES = 4;

function is_space(string $ch): bool {
  return $ch == ' ' || $ch == "\n" || $ch == "\r" || $ch == "\t";
}

// Type: string|array 
function type_value($el) {
  if (!is_string($el)) return $el;

  if ($el === "true") {
    return true;
  }
  if ($el === "false") {
    return false;
  }

  if (is_numeric($el)) {
    if (strpos($el, '.') !== false || strpos($el, 'e') !== false) {
      $el = floatval($el);
    } else {
      $el = intval($el);
    }
  }

  return $el;
}

function kv_decode(string $string, int $flags = 2): array {
  $len = strlen($string);

  $esc = false;
  $inside = false; 
  $header = false;
  $simple_value = true;

  $comment = false;
  $blockcomment = false;
  $resource = false;

  $quotes = false;
  $array_depth = 0;
  $array_keys = [];

  $preserve_comments = (bool)($flags & KV_PRESERVE_COMMENTS);
  $vdata = (bool)($flags & KV_VDATA_FORMAT);
  if ($vdata) $simple_value = false;
  $parse_types = (bool)($flags & KV_PARSE_TYPES);

  $res = [];
  $substr = ""; $name = ""; $prev_name = "";
  $comm = "";
  $inner_brackets = 0;

  for ($i = 0; $i < $len; $i++) {
    $ch = $string[ $i ];
    if ($comment) {
      if ($ch === "\n") {
        $comment = false;
        if ($preserve_comments) {
          $res['#/comment_'.$i] = trim($comm);
          $comm = "";
        }
      } else {
        if ($preserve_comments)
          $comm .= $ch;
      }
    } else if ($blockcomment) {
      if (
        ($ch === '*' && $i < $len-1 && $string[$i+1] === '/') || 
        ($ch === '-' && $i < $len-2 && $string[$i+1] === '-' && $string[$i+2] === '>')
      ) {
        $blockcomment = false;
        if ($ch === '*') {
          $i++;
        } else {
          $i += 2;
        }
        if ($preserve_comments) {
          $res['#/comment_'.$i] = trim($comm);
          $comm = "";
        }
      } else {
        if ($preserve_comments)
          $comm .= $ch;
      }
    } else if ($esc) {
      if ($inside)
        $substr .= $ch;
      else if ($header)
        $name .= $ch;
      $esc = false;
    } else {
      if ($array_depth && $ch == ',') {
        if ($inside) {
          $substr .= $ch;
          continue;
        }
        if ($substr === "" && !empty($name)) {
          $res[ end($array_keys) ][] = $parse_types ? type_value($name) : $name;
          $header = false;
          $name = "";
        }
        if ($substr !== "") {
          $res[ end($array_keys) ][] = $parse_types ? type_value($substr) : $name;
          $substr = "";
        }
        continue;
      } else if ($ch === "\\") {
        $esc = true;
        if ($inside)
          $substr .= $ch;
      } else if ($inside) {
        if ($ch === "{" && !$simple_value) {
          $inner_brackets++;
          $substr .= $ch;
        } else if (($ch === "\"" && $quotes) || ($ch === "}" && !$simple_value) || (!$quotes && $simple_value && is_space($ch))) {
          if ($simple_value) {
            if (($ch !== "\"" && $quotes) && (!$quotes && !is_space($ch))) {
              $substr .= $ch;
              continue;
            }
          } else {
            if ($ch === "\"") {
              $substr .= $ch;
              continue;
            }
          }
          if (!$inner_brackets) {
            if ($simple_value) {
              // removing slashes
              if ($resource) {
                $v = $substr.$ch;
              } else if ($quotes) {
                $v = str_replace("\\\"", "\"", $substr);
              } else {
                $v = $substr;
              }
            } else {
              // getting deeper
              $v = kv_decode($substr, $flags);
            }

            if ($array_depth) {
              $name = end($array_keys);
            }
            
            if ($name === "") $name = $prev_name;

            if ($name === "") {
              if (empty($res)) {
                $res = $parse_types ? type_value($v) : $v;
              } else if ($array_depth) {
                $res[ $name ][] = $parse_types ? type_value($v) : $v;
              } else {
                $res[] = $parse_types ? type_value($v) : $v;
              }
            } else {
              if (!$array_depth) {
                if (isset($res[ $name ])) {
                  if (!\is_array($res[ $name ]))
                    $res[ $name ] = [ $res[ $name ] ];
                  $res[ $name ][] = $parse_types ? type_value($v) : $v;
                } else {
                  $res[ $name ] = $parse_types ? type_value($v) : $v;
                }
              } else {
                $res[ $name ][] = $parse_types ? type_value($v) : $v;
              }
            }
            
            $prev_name = $name;
            // cleaning up
            $name = "";
            $substr = "";
            $inside = false;
            $quotes = false;
            $resource = false;
            if ($vdata) {
              $simple_value = false;
            } else {
              $simple_value = true;
            }
          } else if (!$simple_value) {
            $inner_brackets--;
            $substr .= $ch;
          }
        } else {
          $substr .= $ch;
        }
      } else if ($ch === "\"") {
        if ($i && $string[$i-1] === ":") {
          $substr .= $ch;
          $simple_value = true;
          $quotes = true;
          $inside = true;
          $resource = true;
          continue;
        }
        if ($header) {
          //echo "$name\n";
          $header = false;
          $quotes = false;
        } else if (empty($name)) {
          $header = true;
          $quotes = true;
        } else {
          $simple_value = true;
          $inside = true;
          $quotes = true;
        }
      } else if ($ch === "{") {
        if (!$simple_value && !$quotes && $vdata && $array_depth && $substr !== "") {
          $res[ end($array_keys) ][] = $parse_types ? type_value($substr) : $substr;
          $substr = "";
        }
        $inside = true;
        $simple_value = false;
      } else if (!$header && $ch === '/' && $i && $string[ $i-1 ] === '/') {
        $comment = true;
      } else if (!$header && $ch === '*' && $i && $string[ $i-1 ] === '/') {
        $blockcomment = true;
      } else if (!$header && $ch === '<' && $i < $len-3 && $string[ $i+1 ] === '!' && $string[ $i+2 ] === '-' && $string[ $i+3 ] === '-') {
        $blockcomment = true;
        $i += 3;
      } else if (!$header && $ch === '[') {
        $array_depth++;
        $array_keys[] = $name;
        $res[$name] = [];
      } else if (!$header && $array_depth && $ch === ']') {
        $array_depth--;
        array_pop($array_keys);
        $name = "";
      } else {
        if ($vdata) {
          if (!$header && !$simple_value && !$inside && !$array_depth) {
            if (is_space($ch)) {
              continue;
            }
            if ($ch == '=') {
              // $simple_value = true;
              // $inside = true;
              continue;
            }
            if ($name === "") {
              $header = true;
              $name .= $ch;
            } else {
              $simple_value = true;
              $inside = true;
              $substr .= $ch;
            }
          } else if ($header) {
            if ((($ch == " " || $ch == "=") && !$quotes) || ($quotes && $ch == '"')) {
              $header = false;
              $quotes = false;
            } else {
              $name .= $ch;
            }
          } else if ($array_depth && !is_space($ch) && $ch != ',' && $ch != '"') {
            $substr .= $ch;
          }
        } else {
          if ($header) {
            $name .= $ch;
          }
        }
      }
    }
  }

  if (count($res) == 1 && array_keys($res)[0] === "") {
    $res = [ array_values($res)[0] ];
  }

  return $res;
}
