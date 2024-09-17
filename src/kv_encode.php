<?php 

namespace leamare\SimpleKV;

const KV_VDATA_OUTPUT = 2;

function to_string($el, $vdata = false): string {
  if (!$vdata) {
    return "\"".\addcslashes((string)$el, "\"")."\"";
  }

  if (is_string($el)) {
    if ($vdata && strpos($el, "\n") === false && strpos($el, ':') !== false && $el[strlen($el)-1] === '"') {
      return $el;
    } else {
      return "\"".\addcslashes((string)$el, "\"")."\"";
    }
  }

  if (is_bool($el)) {
    return $el ? "true" : "false";
  }

  if (is_numeric($el)) {
    return (string)$el;
  }
}

function header($el, $vdata = false): string {
  if (!$vdata || is_numeric($el) || strpos($el, '"') !== false) {
    $el = "\"".\addcslashes((string)$el, "\"")."\"";
  }

  return $el;
}

function kv_encode(array $arr, int $flags = 0, int $tabs = 0): string {
  $vdata = (bool)($flags & KV_VDATA_OUTPUT);

  if (!\is_array($arr)) return $arr;
  $res = "";

  if (count(array_filter(array_keys($arr), function($k) { return strpos($k, '#/comment_') !== false; })) > 1 && !$tabs) {
    $arr = [ $arr ];
  }

  $nonComments = 0;
  $commCnt = 0 + $tabs;
  
  foreach ($arr as $k => $v) {
    if (strpos($k, '#/comment_') !== false) {
      if (\is_array($v))
        $v = implode("\n", $v);
      if (strpos($v, "\n") !== false) {
        $res .= "\n/* ".$v."*/\n";
      } else {
        if ($vdata && !$commCnt) {
          $res .= "<!-- $v -->\n";
        } else {
          $res .= "//".$v."\n";
        }
      }
    } else if (is_array($v)) {
      // if (strpos($k, ' ') !== false || strpos($k, "\"") !== false || strpos($k, '/') !== false || 
      //     strpos($k, "\n") !== false || strpos($k, "\t") !== false) {
      // $k = "\"".\addcslashes($k, "\"")."\"";
      //}

      if (array_keys($v) === range(0, count($v) - 1)) {
        if ($vdata) {
          $res .= str_repeat("\t", $tabs).($k == $nonComments && $tabs < 3 ? "" : header($k, $vdata)." = \n").
              str_repeat("\t", $tabs)."[\n";
          foreach($v as $mv) {
            if (is_array($mv)) {
              $mv = kv_encode($mv, $flags, $tabs+2);
              $res .= str_repeat("\t", $tabs+1)."{\n".
                $mv.
                str_repeat("\t", $tabs+1)."},\n";
            } else {
              $mv = to_string($mv, $vdata);
              $res .= str_repeat("\t", $tabs+1).$mv.",\n";
            }
          }
          $res .= str_repeat("\t", $tabs)."]\n";
        } else {
          foreach($v as $mv) {
            if (is_array($mv)) {
              $mv = kv_encode($mv, $flags, $tabs+1);
            } else {
              $mv = to_string($mv, $vdata);
            }
            $res .= str_repeat("\t", $tabs).
              header($k, $vdata).
              ($vdata ? " = " : "\t\t").
              $mv."\n";
          }
        }
      } else {
        $res .= str_repeat("\t", $tabs).
        ($k == $nonComments && $tabs < 3 ? "" : header($k, $vdata).($vdata ? " = " : "")."\n").
        str_repeat("\t", $tabs)."{\n".
        kv_encode($v, $flags, $tabs+1).
        str_repeat("\t", $tabs)."}\n";
      }

      $nonComments++;
    } else {
      $res .= str_repeat("\t", $tabs).
        header($k, $vdata).
        ($vdata ? " = " : "\t\t").
        to_string($v, $vdata)."\n";

      $nonComments++;
    }
  }

  return $res;
}
