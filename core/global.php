<?php
   /*------------------------------------------------------------/
   /                            设置全局变量                      /
   /                          author :　xinde                    /
   /------------------------------------------------------------*/

header("Content-type:text/html;charset=utf-8");
set_time_limit(0);
date_default_timezone_set("PRC");
ini_set('memory_limit', '1024M');
define('ROOT_DIR', dirname(dirname(__FILE__)));

$CONFIG_ALL = parse_ini_file_extended(ROOT_DIR. '/conf/global.ini');
