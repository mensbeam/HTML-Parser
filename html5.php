#!/usr/bin/env php
<?php
namespace dW\HTML5;
require_once 'vendor/autoload.php';

Parser::$debug = true;

var_export(Parser::parse('<!DOCTYPE HtMl'));