<?php 

namespace leamare\SimpleKV;

function kv_encode(array $arr, int $tabs = 0): string {
  if (!\is_array($arr)) return $arr;
  $res = "";

  foreach ($arr as $k => $v) {
    if (strpos($k, '#/comment_') !== false) {
      if (\is_array($v))
        $v = implode("\n", $v);
      if (strpos($v, "\n") !== false) {
        $res .= "\n/* ".$v."*/\n";
      } else {
        $res .= "//".$v."\n";
      }
    } elseif (is_array($v)) {
      // if (strpos($k, ' ') !== false || strpos($k, "\"") !== false || strpos($k, '/') !== false || 
      //     strpos($k, "\n") !== false || strpos($k, "\t") !== false) {
      $k = "\"".\addcslashes($k, "\"")."\"";
      //}

      if (array_keys($v) === range(0, count($v) - 1)) {
        foreach($v as $mv) {
          $res .= str_repeat("\t", $tabs)."$k\t\t\"".\addcslashes((string)$mv, "\"")."\"\n";
        }
      } else {
        $res .= str_repeat("\t", $tabs)."$k\n".
        str_repeat("\t", $tabs)."{\n".
        kv_encode($v, $tabs+1).
        str_repeat("\t", $tabs)."}\n";
      }
    } else {
      $res .= str_repeat("\t", $tabs)."\"".\addcslashes($k, "\"")."\"\t\t\"".\addcslashes((string)$v, "\"")."\"\n";
    }
  }

  return $res;
}
