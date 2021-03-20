<?php

echo '<pre>' . PHP_EOL . 'query took ' .
    round((milliseconds() - $config['timestamp']) / 1000, 2) . ' seconds';
