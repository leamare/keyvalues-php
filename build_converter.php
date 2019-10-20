<?php 

namespace leamare\Dota2BuildFile;

function build_decode($string) {
  $len = strlen($string);

  $esc = false;
  $inside = false; 
  $header = false;
  $simple_value = true;
  $res = [];
  $substr = ""; $name = ""; $prev_name = "";
  $inner_brackets = 0;

  for ($i = 0; $i < $len; $i++) {
    $ch = $string[ $i ];
    if ($esc) {
      if ($inside)
        $substr .= $ch;
      else if ($header)
        $name .= $ch;
      $esc = false;
    } else {
      if ($ch === "\\") {
        $esc = true;
      } else if ($inside) {
        if ($ch === "{") {
          $inner_brackets++;
          $substr .= $ch;
        } else if ($ch === "\"" || $ch === "}") {
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
            // getting deeper
            $v = $simple_value ? $substr : build_decode($substr);
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
      } else {
        if ($header)
          $name .= $ch;
      }
    }
  }

  return $res;
}

function build_encode($arr, $tabs = 0) {
  if (!\is_array($arr)) return $arr;
  $res = "";

  foreach ($arr as $k => $v) {
    if (is_array($v)) {
      if (array_keys($v) === range(0, count($v) - 1)) {
        foreach($v as $mv) {
          $res .= str_repeat("\t", $tabs)."\"".\addcslashes($k, "\"")."\"\t\t\"".\addcslashes($mv, "\"")."\"\n";
        }
      } else {
        $res .= str_repeat("\t", $tabs)."\"".\addcslashes($k, "\"")."\"\n".
        str_repeat("\t", $tabs)."{\n".
        build_encode($v, $tabs+1).
        str_repeat("\t", $tabs)."}\n";
      }
    } else {
      $res .= str_repeat("\t", $tabs)."\"".\addcslashes($k, "\"")."\"\t\t\"".\addcslashes($v, "\"")."\"\n";
    }
  }

  return $res;
}