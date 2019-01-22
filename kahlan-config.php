<?php

$args = $this->commandLine();
$args->option('src', 'default', 'library/');

unset($_SERVER['argc']);
unset($_SERVER['argv']);
