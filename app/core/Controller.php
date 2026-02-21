<?php
namespace App\Core;

class Controller {
    public function view($view, $data = []) {
        // Fix Path: Go up one level from 'core' to 'app', then into 'views'
        $viewFile = dirname(__DIR__) . '/views/' . $view . '.php';
        
        if (file_exists($viewFile)) {
            extract($data);
            require_once $viewFile;
        } else {
            die("View does not exist: " . $view . "<br>Checked path: " . $viewFile);
        }
    }

    public function model($model) {
        $modelClass = 'App\\Models\\' . $model;
        return new $modelClass();
    }
    
    public function redirect($url) {
        header("Location: " . $url);
        exit;
    }
}
