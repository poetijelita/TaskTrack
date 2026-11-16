<?php

require __DIR__ . "/../src/TaskInput.php";
use TaskTrack\TaskInput;
assert(
    TaskInput::validate([
        "title" => "",
        "status" => "todo",
        "priority" => "medium",
    ]) !== [],
);
assert(
    TaskInput::validate([
        "title" => "Tugas",
        "status" => "todo",
        "priority" => "medium",
    ]) === [],
);
assert(
    TaskInput::validate([
        "title" => "Tugas",
        "status" => "invalid",
        "priority" => "medium",
    ]) !== [],
);
echo "Validasi OK\n";
