<?php

function filter_html($val) {
    return htmlspecialchars($val);
}

function filter_url($val) {
    return urlencode($val);
}


error_reporting(E_ALL);
class Engine
{
    protected $storage = [];
    protected $filename = null;
    protected $filters = ["html"=>'filter_html', "url"=>'filter_url'];


    public function load($filename)
    {
        if(!file_exists($filename)) {
            throw new Exception("File not found: $filename", 1);
        }
        $this->filename = $filename;
        $this->on_init();
    }

    protected function on_init() {}

    public function set($key, $value) {
        $this->storage[$key] = $value;
    }

    private function get_var($var, $prevArray=true) {
        $var = explode(".", $var);
        $master = $this->storage;
        $i = 0;
        while($i < count($var)) {
            $current = $var[$i];
            $v = $var[$i];
            if(isset($master[$current])) {
                $master = $master[$current];
            } else {
                return "";
            }
            $i++;
        }
        if(!is_array($master) || !$prevArray) {
            return $master;
        }
        return "";
        
    }

    private function eval_term($term) {
        $term = str_split($term);
        $term[] = "EOF";
        $i = 0;
        $tokens = [];
        $thistok = "";
        $toktype = "none";
        while($i < count($term)) {
            $tt = $term[$i];
            if($tt == "\"") {
                if($toktype == "none") {
                    $thistok = "";
                    $toktype = "string";
                } else {
                    $tokens[] = $thistok;
                    $toktype = "none";
                    $thistok = "";
                }
                $i++;
                continue;
            } else if($toktype == "string") {
                $thistok .= $tt;
                $i++;
                continue;
            } else if($tt == " " || $tt == "EOF") {
                if($toktype == "variable") {
                    $thistok = substr($thistok, 1, strlen($thistok) -1);
                    $tokens[] = $this->get_var($thistok); 
                } else if($toktype == "number") {
                    $tokens[] = ((int)$thistok);
                }
                $toktype = "none";
                $thistok = "";
                $i++;
                continue;
            } if($toktype == "none" && $tt == "$") {
                $toktype = "variable";
                $thistok = "";
            } else if($toktype == "variable") {
                if(!preg_match("~^\\$([a-zA-Z_][a-zA-Z0-9_.]*)$~", ($thistok . $tt))) {
                    $thistok = substr($thistok, 1, strlen($thistok) -1);
                    $tokens[] = $this->get_var($thistok);
                    $thistok = "";
                    $toktype = "none";
                    $i++;
                    continue;
                }
            } else if(preg_match("~^(-?[0-9]+(\\.[0-9]+)?)$~", $thistok . $tt)) {
                $toktype = "number";
            } else if($toktype == "number") {
                $tokens[] = $thistok;
                $toktype = "none";
                $thistok = "";
                $i++;
                continue;
            } else if(in_array($thistok . $tt, ["true", "false", "==", "!=", "<", ">", "&&","||"])) {
                if($thistok === "tru") {
                    $thistok = (boolean) true;
                    $tokens[] = $thistok;
                } else if($thistok === "fals") {
                    $thistok = (boolean) false;
                    $tokens[] = $thistok;
                } else {
                    $tokens[] = $thistok . $tt;
                }
                $toktype = "none";
                $thistok = "";
                $i += strlen($thistok . ((string)$tt));
                continue;
            }
            $thistok = $thistok . $tt;

            $i++;
        }
        $term = $tokens;
        $i = 0;
        while($i < count($term)) {
            $tt = $term[$i];
            if($tt === "==") {
                $is_true = $term[$i-1] === $term[$i+1];
                $term = array_merge(array_slice($term, 0, $i-1), [$is_true], array_slice($term, $i+2, count($term)));
                $i = 0; continue;
            } else if($tt === "!=") {
                $is_true = $term[$i-1] !== $term[$i+1];
                $term = array_merge(array_slice($term, 0, $i-1), [$is_true], array_slice($term, $i+2, count($term)));
                $i = 0; continue;
            } else if($tt === ">") {
                $is_true = $term[$i-1] > $term[$i+1];
                $term = array_merge(array_slice($term, 0, $i-1), [$is_true], array_slice($term, $i+2, count($term)));
                $i = 0; continue;
            }  else if($tt === "<") {
                $is_true = $term[$i-1] < $term[$i+1];
                $term = array_merge(array_slice($term, 0, $i-1), [$is_true], array_slice($term, $i+2, count($term)));
                $i = 0; continue;
            }
            $i += 1;
        }
        $i = 0;
        while($i < count($term)) {
            $tt = $term[$i];
            if($tt === "&&") {
                $is_true = $term[$i-1] && $term[$i+1];
                $term = array_merge(array_slice($term, 0, $i-1), [$is_true], array_slice($term, $i+2, count($term)));
                $i = -1;
            } else if($tt === "||") {
                $is_true = $term[$i-1] || $term[$i+1];
                $term = array_merge(array_slice($term, 0, $i-1), [$is_true], array_slice($term, $i+2, count($term)));
                $i = -1;
            }
            $i += 1;
        }
        if(count($term) == 1) {
            return $term[0];
        }
        return false;
    }

    private function parse($html) {
        $tokens = [];
        $token_type = "text";
        $token_value = "";
        $string = str_split($html, 1);
        $i = 0;
        while($i < count($string)) {
            $char = $string[$i];
            if($token_type == "text" && $char == "[") {
                if(trim($token_value) != "") {
                    $tokens[] = [$token_type, trim($token_value)];
                }
                $token_value = "";
                $token_type = "in_bracket";
            } else if($token_type == "in_bracket" && $char == "]") {
                $tokens[] = [$token_type, trim($token_value)];
                $token_value = "";
                $token_type = "text";
            } else {
                $token_value .= $char;
            }
            $i += 1;

        }
        $tokens[] = [$token_type, trim($token_value)];
        return $this->evaluate($tokens);
    }

    private function evaluate($tokens) {
        $import = null;
        $area_extensions = [];
        $i = 0;
        while ($i < count($tokens)) {
            $tok = $tokens[$i];
            if($tok[0] == "in_bracket") {
                if(preg_match("~^\\$([a-zA-Z_][a-zA-Z0-9_.]+)(?:\|([a-z+_]+))?$~", $tok[1], $m)) {
                    $variable_name = $m[1];
                    $filter = "";
                    if(isset($m[2])) {
                        $filter = $m[2];
                    }
                    $content = $this->get_var($m[1]);
                    $filter = explode("+", $filter);
                    foreach($filter as $f) {
                        if(in_array($f, array_keys($this->filters))) {
                            $ffunc = $this->filters[$f];
                            $content = $ffunc($content);
                        }
                    }
                    $tokens[$i] = ["text", $content];

                } else if(preg_match("|^@master \"([a-zA-Z0-9._/-]+\.shaffel)\"$|", $tok[1], $m)) {
                    $path = dirname($this->filename) . "/" . $m[1];
                    if(!file_exists($path)) {
                        throw new Exception("File not found: $filename", 1);
                    }
                    $master_html = file_get_contents($path);
                    unset($tokens[$i]);
                    $tokens = array_values($tokens);
                    $tokens = array_merge($this->parse($master_html), $tokens);
                    $i = 0;
                } else if(preg_match("|^@extend \"([a-zA-Z0-9_-]+)\"$|", $tok[1], $m)) {
                    $area_name = $m[1];
                    $recurring = [];
                    $i += 1;
                    $very_old_i = $i;
                    while($i < count($tokens)) {
                        $tok = $tokens[$i];
                        if($tok == ["in_bracket", "/@extend"]) {
                            break;
                        }
                        $recurring[] = $tok;
                        $i++;
                    }
                    $tokens = array_merge(array_slice($tokens, 0, $very_old_i - 1), array_slice($tokens, $i + 1, count($tokens)));
                    $old_i = $very_old_i;
                    $i = 0;
                    while($i < count($tokens)) {
                        $tok = $tokens[$i];
                        if($tok == ["in_bracket", "@area \"".$area_name."\""]) {
                            $tokens = array_merge(array_slice($tokens, 0, $i), $recurring, array_slice($tokens, $i + 1, count($tokens)));
                        }
                        $i++;
                    }
                    $i = 0;
                } else if(preg_match("|^@if\\((.*?)\\)$|", $tok[1], $m)) {
                    $ignore = !$this->eval_term($m[1]);
                    $has_been_evaled = !$ignore;
                    $left = [];
                    $i += 1;
                    $old_i = $i;
                    while($i < count($tokens)) {
                        $tok = $tokens[$i];
                        if(preg_match("|^@elseif\\((.*?)\\)$|", $tok[1], $m)) {
                            if($has_been_evaled) {
                                $ignore = true;
                            } else {
                                $evaled = $this->eval_term($m[1]);
                                if($evaled) {
                                    $ignore = false;
                                    $has_been_evaled = true;
                                }
                            }
                        } else if($tok == ["in_bracket", "@else"]) {
                            if($has_been_evaled) {
                                $ignore = true;
                            } else {
                                $ignore = false;
                                $has_been_evaled = true;
                            }
                        } else if($tok == ["in_bracket", "/@if"]) {
                            break;
                        } else if(!$ignore) {
                            $left[] = $tok;
                        }
                        $i++;
                    }
                    $tokens = array_merge(array_slice($tokens, 0, $old_i-1), $left, array_slice($tokens, $i+1, count($tokens)));
                } else if(preg_match("|^@each\\(\\$([a-zA-Z_][a-zA-Z0-9_.]+) \\-\\> \\$([a-zA-Z_][a-zA-Z0-9_.]+)\\)$|", $tok[1], $m)) {
                    $list_name = $m[1];
                    $item_name = $m[2];
                    $recurring = [];
                    $i += 1;
                    $very_old_i = $i;
                    while($i < count($tokens)) {
                        $tok = $tokens[$i];
                        if($tok == ["in_bracket", "/@each"]) {
                            break;
                        }
                        $recurring[] = $tok;
                        $i++;
                    }
                    $curry = [];
                    $old_item_name = null;
                    if(in_array($item_name, $this->storage)) {
                        $old_item_name = $this->storage[$item_name];
                    }
                    foreach($this->get_var($list_name, false) as $item) {
                        $this->storage[$item_name] = $item;
                        $curry = array_merge($curry, $this->evaluate($recurring));
                    }
                    if($old_item_name != null) {
                        $this->storage[$item_name] = $old_item_name;
                    } else {
                        unset($this->storage[$item_name]);
                    }
                    $tokens = array_merge(array_slice($tokens, 0, $very_old_i - 1), $curry, array_slice($tokens, $i + 1, count($tokens)));
                }
            }
            $i++;
        }

        return $tokens;
    }

    public function toHTML() {
        $html = file_get_contents($this->filename);
        
        $tokens = $this->parse($html);

        $html = [];
        foreach($tokens as $tok) {
            $html[] = $tok[1];
        }
        $html = implode("", $html);

        return $html;
    }

    public function show() {
        echo $this->toHTML();
        die();
    }
}

?>