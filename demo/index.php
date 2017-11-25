<?php
require "../lib/engine.php";
function filter_italic($val) {
    return "<i>".$val."</i>";
}
class CustomEngine extends Engine
{
    protected function on_init() {
        $this->filters["italic"] = 'filter_italic';
    }
}


$page = new CustomEngine();
$page->load("template/index.shaffel");
$page->set("title", "Startseite");
$page->set("list", 
    [
        "de"=> [
            "name" => "Deutsch",
            "hello" => "Hallo",
            "bye" => "Auf Wiedersehen"
        ],
        "en" => [
            "name" => "English",
            "hello" => "Hello",
            "bye" => "Auf Wiedersehen"
        ]
    ]
);
$page->set("x", 3);

$page->show();
?>