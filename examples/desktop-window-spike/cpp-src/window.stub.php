<?php

function win_create_window(string $title, int $width, int $height): int {}
function win_show_window(int $window): void {}
function win_pump_events(): bool {}
function win_close_window(int $window): void {}
