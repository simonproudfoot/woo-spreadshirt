<?php

class wooSpreadPluginDeactivate
{
  public static function deactivate() {
    flush_rewrite_rules();
  }
}
