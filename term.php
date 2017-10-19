<?php
function confirm() {
  warn("This is going to clear out your destination database.");
  $line = readLine("Type yes to continue: ");
  if ($line != "yes") {
    echo warn("Exiting with no changes.\n");
    exit();
  }
  echo "Beginning migration.\n";
}

function red($str) {
  return "\033[41m$str\033[0m";
}

function blue($str) {
  return "\033[1;34m$str\033[0m";
}

function warn($str) {
  echo red("[WARN]") . " $str\n";
}
