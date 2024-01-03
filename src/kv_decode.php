<?php 

namespace leamare\SimpleKV;

const KV_PRESERVE_COMMENTS = 1;

function kv_decode(string $string, int $flags = 0): array {
  $len = strlen($string);

  $esc = false;
  $inside = false; 
  $header = false;
  $simple_value = true;

  $comment = false;
  $blockcomment = false;

  $preserve_comments = (bool)($flags & KV_PRESERVE_COMMENTS);

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
          $res['#/comment_'.$i] = $comm;
          $comm = "";
        }
      } else {
        if ($preserve_comments)
          $comm .= $ch;
      }
    } else if ($blockcomment) {
      if ($ch === "*" && $i < $len-1 && $string[$i+1] === '/') {
        $blockcomment = false;
        $i++;
        if ($preserve_comments) {
          $res['#/comment_'.$i] = $comm;
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
      if ($ch === "\\") {
        $esc = true;
        if ($inside)
          $substr .= $ch;
      } else if ($inside) {
        if ($ch === "{" && !$simple_value) {
          $inner_brackets++;
          $substr .= $ch;
        } else if ($ch === "\"" || ($ch === "}" && !$simple_value)) {
          if ($simple_value) {
            if ($ch !== "\"") {
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
              $v = str_replace("\\\"", "\"", $substr);
            } else {
              // getting deeper
              $v = kv_decode($substr, $flags);
            }
            
            if (empty($name)) $name = $prev_name;
            if (isset($res[ $name ])) {
              if (!\is_array($res[ $name ]))
                $res[ $name ] = [ $res[ $name ] ];
              $res[ $name ][] = $v;
            } else {
              $res[ $name ] = $v;
            }
            $prev_name = $name;
            // cleaning up
            $name = "";
            $substr = "";
            $inside = false;
            $simple_value = true;
          } else if (!$simple_value) {
            $inner_brackets--;
            $substr .= $ch;
          }
        } else {
          $substr .= $ch;
        }
      } else if ($ch === "\"") {
        if ($header) {
          //echo "$name\n";
          $header = false;
        } else if (empty($name)) {
          $header = true;
        } else {
          $simple_value = true;
          $inside = true;
        }
      } else if ($ch === "{") {
        $inside = true;
        $simple_value = false;
      } else if (!$header && $ch === "/" && $i && $string[ $i-1 ] === "/") {
        $comment = true;
      } else if (!$header && $ch === "*" && $i && $string[ $i-1 ] === "/") {
        $blockcomment = true;
      } else {
        if ($header)
          $name .= $ch;
      }
    }
  }

  return $res;
}
